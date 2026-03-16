<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subdomain;
use App\Models\SubdomainBlacklist;
use App\Models\SubdomainDomain;
use App\Models\SubdomainLog;
use App\Services\CloudflareService;
use App\Exceptions\CloudflareException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubdomainController extends Controller
{
    public function __construct(
        protected CloudflareService $cloudflare
    ) {}

    // =========================================================================
    // Dashboard
    // =========================================================================

    /**
     * Show the admin dashboard with statistics and recent subdomains.
     */
    public function index(): View
    {
        $stats = [
            'total' => Subdomain::count(),
            'active' => Subdomain::where('status', 'active')->count(),
            'pending' => Subdomain::where('status', 'pending')->count(),
            'suspended' => Subdomain::where('status', 'suspended')->count(),
            'error' => Subdomain::where('status', 'error')->count(),
            'today' => Subdomain::whereDate('created_at', today())->count(),
            'this_week' => Subdomain::whereBetween('created_at', [now()->startOfWeek(), now()])->count(),
            'this_month' => Subdomain::whereBetween('created_at', [now()->startOfMonth(), now()])->count(),
        ];

        $recentSubdomains = Subdomain::with(['domain', 'user'])
            ->latest()
            ->take(10)
            ->get();

        $domains = SubdomainDomain::withCount('subdomains')->get();

        return view('subdomains::admin.subdomains.index', compact('stats', 'recentSubdomains', 'domains'));
    }

    // =========================================================================
    // Settings
    // =========================================================================

    /**
     * Show the settings page.
     */
    public function settings(): View
    {
        $settings = [
            'cloudflare_api_token' => Subdomain::getSetting('cloudflare_api_token') ? '********' : '',
            'min_length' => Subdomain::getSetting('min_length', '3'),
            'max_length' => Subdomain::getSetting('max_length', '32'),
            'change_cooldown_hours' => Subdomain::getSetting('change_cooldown_hours', '24'),
            'auto_delete_on_terminate' => Subdomain::getSetting('auto_delete_on_terminate', 'true'),
            'auto_suspend_on_suspend' => Subdomain::getSetting('auto_suspend_on_suspend', 'true'),
            'default_ttl' => Subdomain::getSetting('default_ttl', '1'),
        ];

        $domains = SubdomainDomain::orderBy('is_default', 'desc')->orderBy('domain')->get();

        return view('subdomains::admin.subdomains.settings', compact('settings', 'domains'));
    }

    /**
     * Save settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'min_length' => 'required|integer|min:1|max:63',
            'max_length' => 'required|integer|min:1|max:63|gte:min_length',
            'change_cooldown_hours' => 'required|integer|min:0',
            'auto_delete_on_terminate' => 'required|in:true,false',
            'auto_suspend_on_suspend' => 'required|in:true,false',
            'default_ttl' => 'required|in:1,60,300,1800,3600,43200,86400',
        ]);

        // Only update API token if a new one was provided (not the masked placeholder)
        if ($request->filled('cloudflare_api_token') && $request->input('cloudflare_api_token') !== '********') {
            Subdomain::setSetting('cloudflare_api_token', $request->input('cloudflare_api_token'));
        }

        $settingKeys = [
            'min_length', 'max_length', 'change_cooldown_hours',
            'auto_delete_on_terminate', 'auto_suspend_on_suspend', 'default_ttl',
        ];

        foreach ($settingKeys as $key) {
            Subdomain::setSetting($key, $request->input($key));
        }

        return redirect()
            ->route('admin.subdomains.settings')
            ->with('success', __('subdomains::subdomains.settings_saved'));
    }

    /**
     * Test Cloudflare connection (AJAX).
     */
    public function testConnection(Request $request): JsonResponse
    {
        $request->validate([
            'zone_id' => 'required|string|max:64',
        ]);

        try {
            $result = $this->cloudflare->testConnection($request->input('zone_id'));

            return response()->json([
                'success' => true,
                'message' => __('subdomains::subdomains.connection_test_success'),
                'data' => $result,
            ]);
        } catch (CloudflareException $e) {
            return response()->json([
                'success' => false,
                'message' => __('subdomains::subdomains.cloudflare_connection_failed', ['error' => $e->getMessage()]),
            ], 422);
        }
    }

    // =========================================================================
    // Domain Management
    // =========================================================================

    /**
     * Add a new domain.
     */
    public function addDomain(Request $request): RedirectResponse
    {
        $request->validate([
            'domain' => 'required|string|max:255|unique:pteroca_subdomain_domains,domain',
            'cloudflare_zone_id' => 'required|string|max:64',
            'is_default' => 'sometimes|boolean',
        ]);

        // If this is set as default, unset others
        if ($request->boolean('is_default')) {
            SubdomainDomain::where('is_default', true)->update(['is_default' => false]);
        }

        SubdomainDomain::create([
            'domain' => strtolower($request->input('domain')),
            'cloudflare_zone_id' => $request->input('cloudflare_zone_id'),
            'is_default' => $request->boolean('is_default'),
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.subdomains.settings')
            ->with('success', __('subdomains::subdomains.domain_added'));
    }

    /**
     * Update an existing domain.
     */
    public function updateDomain(Request $request, int $id): RedirectResponse
    {
        $domain = SubdomainDomain::findOrFail($id);

        $request->validate([
            'domain' => 'required|string|max:255|unique:pteroca_subdomain_domains,domain,' . $id,
            'cloudflare_zone_id' => 'required|string|max:64',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($request->boolean('is_default')) {
            SubdomainDomain::where('is_default', true)->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $domain->update([
            'domain' => strtolower($request->input('domain')),
            'cloudflare_zone_id' => $request->input('cloudflare_zone_id'),
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.subdomains.settings')
            ->with('success', __('subdomains::subdomains.domain_updated'));
    }

    /**
     * Delete a domain (only if it has no active subdomains).
     */
    public function deleteDomain(int $id): RedirectResponse
    {
        $domain = SubdomainDomain::findOrFail($id);

        if ($domain->hasActiveSubdomains()) {
            return redirect()
                ->route('admin.subdomains.settings')
                ->with('error', __('subdomains::subdomains.cannot_delete_domain_in_use'));
        }

        $domain->delete();

        return redirect()
            ->route('admin.subdomains.settings')
            ->with('success', __('subdomains::subdomains.domain_deleted'));
    }

    // =========================================================================
    // Blacklist
    // =========================================================================

    /**
     * Show blacklist management page.
     */
    public function blacklist(Request $request): View
    {
        $query = SubdomainBlacklist::query();

        if ($request->filled('search')) {
            $query->where('word', 'like', '%' . $request->input('search') . '%');
        }

        $blacklist = $query->orderBy('word')->paginate(25)->withQueryString();
        $totalCount = SubdomainBlacklist::count();

        return view('subdomains::admin.subdomains.blacklist', compact('blacklist', 'totalCount'));
    }

    /**
     * Add a word to the blacklist.
     */
    public function addToBlacklist(Request $request): RedirectResponse
    {
        $request->validate([
            'word' => 'required|string|max:63|unique:pteroca_subdomain_blacklist,word',
            'reason' => 'nullable|string|max:255',
        ]);

        SubdomainBlacklist::create([
            'word' => strtolower(trim($request->input('word'))),
            'reason' => $request->input('reason'),
        ]);

        return redirect()
            ->route('admin.subdomains.blacklist')
            ->with('success', __('subdomains::subdomains.blacklist_added'));
    }

    /**
     * Remove a word from the blacklist.
     */
    public function removeFromBlacklist(int $id): RedirectResponse
    {
        SubdomainBlacklist::findOrFail($id)->delete();

        return redirect()
            ->route('admin.subdomains.blacklist')
            ->with('success', __('subdomains::subdomains.blacklist_removed'));
    }

    /**
     * Import blacklist from a text file (one word per line).
     */
    public function importBlacklist(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:txt,csv|max:1024',
        ]);

        try {
            $content = file_get_contents($request->file('file')->getRealPath());
            $words = array_filter(
                array_map('trim', preg_split('/[\r\n,]+/', $content)),
                fn(string $word) => $word !== ''
            );

            $count = 0;
            foreach ($words as $word) {
                $inserted = SubdomainBlacklist::firstOrCreate(
                    ['word' => strtolower($word)],
                    ['reason' => __('subdomains::subdomains.import')]
                );
                if ($inserted->wasRecentlyCreated) {
                    $count++;
                }
            }

            return redirect()
                ->route('admin.subdomains.blacklist')
                ->with('success', __('subdomains::subdomains.blacklist_imported', ['count' => $count]));
        } catch (\Exception $e) {
            Log::error('Blacklist import failed', ['error' => $e->getMessage()]);

            return redirect()
                ->route('admin.subdomains.blacklist')
                ->with('error', __('subdomains::subdomains.import_failed', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Export blacklist as a text file.
     */
    public function exportBlacklist(): StreamedResponse
    {
        $words = SubdomainBlacklist::orderBy('word')->pluck('word');

        return response()->streamDownload(function () use ($words) {
            echo $words->implode("\n");
        }, 'blacklist_' . date('Y-m-d') . '.txt', [
            'Content-Type' => 'text/plain',
        ]);
    }

    /**
     * Load the default blacklist from config.
     */
    public function loadDefaultBlacklist(): RedirectResponse
    {
        $defaults = config('subdomains.default_blacklist', []);
        $count = 0;

        foreach ($defaults as $word) {
            $inserted = SubdomainBlacklist::firstOrCreate(
                ['word' => strtolower($word)],
                ['reason' => 'Default blacklist']
            );
            if ($inserted->wasRecentlyCreated) {
                $count++;
            }
        }

        return redirect()
            ->route('admin.subdomains.blacklist')
            ->with('success', __('subdomains::subdomains.default_blacklist_loaded') . " ({$count} new)");
    }

    // =========================================================================
    // Activity Logs
    // =========================================================================

    /**
     * Show activity logs with filters.
     */
    public function logs(Request $request): View
    {
        $query = SubdomainLog::with(['subdomain.domain', 'user'])->latest();

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->paginate(25)->withQueryString();

        $actions = ['create', 'update', 'delete', 'suspend', 'unsuspend', 'error'];

        return view('subdomains::admin.subdomains.logs', compact('logs', 'actions'));
    }

    /**
     * Clear all logs.
     */
    public function clearLogs(): RedirectResponse
    {
        SubdomainLog::truncate();

        return redirect()
            ->route('admin.subdomains.logs')
            ->with('success', __('subdomains::subdomains.logs_cleared'));
    }

    // =========================================================================
    // Bulk Operations
    // =========================================================================

    /**
     * Synchronize DNS records with Cloudflare.
     */
    public function syncDns(): RedirectResponse
    {
        $subdomains = Subdomain::with('domain')->where('status', 'active')->get();
        $synced = 0;
        $errors = 0;

        foreach ($subdomains as $subdomain) {
            try {
                $zoneId = $subdomain->domain->cloudflare_zone_id;
                $fullName = $subdomain->subdomain . '.' . $subdomain->domain->domain;

                // Check if A record still exists
                $aRecord = $this->cloudflare->recordExists($zoneId, $fullName, 'A');

                if (!$aRecord && $subdomain->cloudflare_a_record_id) {
                    $subdomain->update([
                        'status' => 'error',
                        'error_message' => 'A record not found in Cloudflare during sync',
                    ]);
                    SubdomainLog::log('error', $subdomain->id, null, [
                        'message' => 'A record missing during sync',
                        'expected_record_id' => $subdomain->cloudflare_a_record_id,
                    ]);
                    $errors++;
                    continue;
                }

                $synced++;
            } catch (CloudflareException $e) {
                $subdomain->update([
                    'status' => 'error',
                    'error_message' => 'Sync failed: ' . $e->getMessage(),
                ]);
                $errors++;
            }
        }

        $message = __('subdomains::subdomains.dns_synced') . " ({$synced} OK, {$errors} errors)";

        return redirect()
            ->route('admin.subdomains.index')
            ->with($errors > 0 ? 'warning' : 'success', $message);
    }

    /**
     * Export all subdomains as CSV.
     */
    public function exportSubdomains(): StreamedResponse
    {
        $subdomains = Subdomain::with(['domain', 'user'])->get();

        return response()->streamDownload(function () use ($subdomains) {
            $handle = fopen('php://output', 'w');

            // CSV header
            fputcsv($handle, [
                'ID', 'Subdomain', 'Domain', 'Full Address', 'Server ID',
                'User ID', 'User Email', 'Status', 'A Record ID',
                'SRV Record ID', 'Created At', 'Last Changed At',
            ]);

            foreach ($subdomains as $subdomain) {
                fputcsv($handle, [
                    $subdomain->id,
                    $subdomain->subdomain,
                    $subdomain->domain->domain ?? 'N/A',
                    $subdomain->full_address,
                    $subdomain->server_id,
                    $subdomain->user_id,
                    $subdomain->user->email ?? 'N/A',
                    $subdomain->status,
                    $subdomain->cloudflare_a_record_id,
                    $subdomain->cloudflare_srv_record_id,
                    $subdomain->created_at,
                    $subdomain->last_changed_at,
                ]);
            }

            fclose($handle);
        }, 'subdomains_' . date('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
