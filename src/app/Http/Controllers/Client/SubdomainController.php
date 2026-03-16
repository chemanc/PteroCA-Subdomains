<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Exceptions\CloudflareException;
use App\Models\Subdomain;
use App\Models\SubdomainBlacklist;
use App\Models\SubdomainDomain;
use App\Models\SubdomainLog;
use App\Services\CloudflareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SubdomainController extends Controller
{
    public function __construct(
        protected CloudflareService $cloudflare
    ) {}

    // =========================================================================
    // Show
    // =========================================================================

    /**
     * Show the subdomain management page for a server.
     */
    public function show(Request $request, int $serverId): View
    {
        $server = $this->getAuthorizedServer($serverId);

        $subdomain = Subdomain::with('domain')->forServer($serverId)->first();

        $domains = SubdomainDomain::active()->get();

        $cooldownHours = (int) Subdomain::getSetting('change_cooldown_hours', 24);
        $cooldownRemaining = $subdomain?->getCooldownRemaining($cooldownHours);

        $minLength = (int) Subdomain::getSetting('min_length', 3);
        $maxLength = (int) Subdomain::getSetting('max_length', 32);

        return view('subdomains::client.subdomains.manage', compact(
            'server', 'subdomain', 'domains', 'cooldownRemaining', 'minLength', 'maxLength'
        ));
    }

    // =========================================================================
    // Create
    // =========================================================================

    /**
     * Create a new subdomain for a server.
     */
    public function store(Request $request, int $serverId): RedirectResponse
    {
        $server = $this->getAuthorizedServer($serverId);

        // Check if server already has a subdomain
        if (Subdomain::forServer($serverId)->exists()) {
            return back()->with('error', __('subdomains::subdomains.already_has_subdomain'));
        }

        // Validate input
        $validated = $this->validateSubdomainInput($request);
        $subdomainName = strtolower($validated['subdomain']);
        $domainId = (int) $validated['domain_id'];

        $domain = SubdomainDomain::active()->findOrFail($domainId);

        // Check blacklist
        if (SubdomainBlacklist::isBlacklisted($subdomainName)) {
            return back()
                ->withInput()
                ->withErrors(['subdomain' => __('subdomains::subdomains.subdomain_blacklisted')]);
        }

        // Check availability in local DB
        if (Subdomain::where('subdomain', $subdomainName)->where('domain_id', $domainId)->exists()) {
            return back()
                ->withInput()
                ->withErrors(['subdomain' => __('subdomains::subdomains.subdomain_taken')]);
        }

        // Check availability in Cloudflare
        try {
            $fullName = $subdomainName . '.' . $domain->domain;
            $existing = $this->cloudflare->recordExists($domain->cloudflare_zone_id, $fullName, 'A');

            if ($existing) {
                return back()
                    ->withInput()
                    ->withErrors(['subdomain' => __('subdomains::subdomains.subdomain_taken_cloudflare')]);
            }
        } catch (CloudflareException $e) {
            Log::error('Cloudflare availability check failed', ['error' => $e->getMessage()]);
            return back()
                ->withInput()
                ->with('error', __('subdomains::subdomains.cloudflare_error'));
        }

        // Create DNS records in Cloudflare
        try {
            $serverIp = $this->getServerIp($server);
            $serverPort = $this->getServerPort($server);
            $ttl = (int) Subdomain::getSetting('default_ttl', 1);

            $records = $this->cloudflare->createSubdomainRecords(
                $domain->cloudflare_zone_id,
                $subdomainName,
                $domain->domain,
                $serverIp,
                $serverPort,
                $ttl
            );

            // Save to database
            $subdomain = Subdomain::create([
                'server_id' => $serverId,
                'user_id' => auth()->id(),
                'subdomain' => $subdomainName,
                'domain_id' => $domainId,
                'cloudflare_a_record_id' => $records['a_record_id'],
                'cloudflare_srv_record_id' => $records['srv_record_id'],
                'status' => 'active',
                'last_changed_at' => now(),
            ]);

            // Log the action
            SubdomainLog::log('create', $subdomain->id, auth()->id(), [
                'subdomain' => $subdomainName,
                'domain' => $domain->domain,
                'server_id' => $serverId,
                'server_ip' => $serverIp,
                'server_port' => $serverPort,
            ], $request->ip());

            return redirect()
                ->route('client.subdomain.show', $serverId)
                ->with('success', __('subdomains::subdomains.subdomain_created'));

        } catch (CloudflareException $e) {
            Log::error('Failed to create subdomain DNS records', [
                'subdomain' => $subdomainName,
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);

            SubdomainLog::log('error', null, auth()->id(), [
                'action' => 'create',
                'subdomain' => $subdomainName,
                'error' => $e->getMessage(),
            ], $request->ip());

            return back()
                ->withInput()
                ->with('error', __('subdomains::subdomains.cloudflare_record_failed', ['error' => $e->getMessage()]));
        }
    }

    // =========================================================================
    // Update
    // =========================================================================

    /**
     * Update (change) the subdomain for a server.
     */
    public function update(Request $request, int $serverId): RedirectResponse
    {
        $server = $this->getAuthorizedServer($serverId);

        $existing = Subdomain::with('domain')->forServer($serverId)->firstOrFail();

        // Check cooldown
        $cooldownHours = (int) Subdomain::getSetting('change_cooldown_hours', 24);
        if ($existing->isOnCooldown($cooldownHours)) {
            $remaining = $existing->getCooldownRemaining($cooldownHours);
            return back()->with('error', __('subdomains::subdomains.cooldown_active', ['time' => $remaining]));
        }

        // Validate input
        $validated = $this->validateSubdomainInput($request);
        $newSubdomainName = strtolower($validated['subdomain']);
        $domainId = (int) $validated['domain_id'];

        $domain = SubdomainDomain::active()->findOrFail($domainId);

        // Check blacklist
        if (SubdomainBlacklist::isBlacklisted($newSubdomainName)) {
            return back()
                ->withInput()
                ->withErrors(['subdomain' => __('subdomains::subdomains.subdomain_blacklisted')]);
        }

        // Check availability (exclude current record)
        $duplicate = Subdomain::where('subdomain', $newSubdomainName)
            ->where('domain_id', $domainId)
            ->where('id', '!=', $existing->id)
            ->exists();

        if ($duplicate) {
            return back()
                ->withInput()
                ->withErrors(['subdomain' => __('subdomains::subdomains.subdomain_taken')]);
        }

        try {
            $serverIp = $this->getServerIp($server);
            $serverPort = $this->getServerPort($server);
            $ttl = (int) Subdomain::getSetting('default_ttl', 1);

            // Delete old DNS records
            $oldZoneId = $existing->domain->cloudflare_zone_id;
            $this->cloudflare->deleteSubdomainRecords(
                $oldZoneId,
                $existing->cloudflare_a_record_id,
                $existing->cloudflare_srv_record_id
            );

            // Create new DNS records
            $records = $this->cloudflare->createSubdomainRecords(
                $domain->cloudflare_zone_id,
                $newSubdomainName,
                $domain->domain,
                $serverIp,
                $serverPort,
                $ttl
            );

            $oldSubdomain = $existing->subdomain;
            $oldDomain = $existing->domain->domain;

            // Update database
            $existing->update([
                'subdomain' => $newSubdomainName,
                'domain_id' => $domainId,
                'cloudflare_a_record_id' => $records['a_record_id'],
                'cloudflare_srv_record_id' => $records['srv_record_id'],
                'status' => 'active',
                'error_message' => null,
                'last_changed_at' => now(),
            ]);

            // Log the action
            SubdomainLog::log('update', $existing->id, auth()->id(), [
                'old_subdomain' => $oldSubdomain . '.' . $oldDomain,
                'new_subdomain' => $newSubdomainName . '.' . $domain->domain,
                'server_id' => $serverId,
            ], $request->ip());

            return redirect()
                ->route('client.subdomain.show', $serverId)
                ->with('success', __('subdomains::subdomains.subdomain_updated'));

        } catch (CloudflareException $e) {
            Log::error('Failed to update subdomain DNS records', [
                'subdomain' => $newSubdomainName,
                'error' => $e->getMessage(),
            ]);

            SubdomainLog::log('error', $existing->id, auth()->id(), [
                'action' => 'update',
                'error' => $e->getMessage(),
            ], $request->ip());

            return back()
                ->withInput()
                ->with('error', __('subdomains::subdomains.cloudflare_record_failed', ['error' => $e->getMessage()]));
        }
    }

    // =========================================================================
    // Delete
    // =========================================================================

    /**
     * Delete the subdomain for a server.
     */
    public function destroy(Request $request, int $serverId): RedirectResponse
    {
        $server = $this->getAuthorizedServer($serverId);

        $subdomain = Subdomain::with('domain')->forServer($serverId)->firstOrFail();

        try {
            // Delete DNS records from Cloudflare
            $zoneId = $subdomain->domain->cloudflare_zone_id;
            $this->cloudflare->deleteSubdomainRecords(
                $zoneId,
                $subdomain->cloudflare_a_record_id,
                $subdomain->cloudflare_srv_record_id
            );
        } catch (\Exception $e) {
            Log::warning('Failed to delete DNS records during subdomain deletion', [
                'subdomain_id' => $subdomain->id,
                'error' => $e->getMessage(),
            ]);
            // Continue with DB deletion even if Cloudflare fails
        }

        $subdomainName = $subdomain->subdomain . '.' . $subdomain->domain->domain;

        // Log before deletion
        SubdomainLog::log('delete', $subdomain->id, auth()->id(), [
            'subdomain' => $subdomainName,
            'server_id' => $serverId,
        ], $request->ip());

        $subdomain->delete();

        return redirect()
            ->route('client.subdomain.show', $serverId)
            ->with('success', __('subdomains::subdomains.subdomain_deleted'));
    }

    // =========================================================================
    // AJAX: Check Availability
    // =========================================================================

    /**
     * Check subdomain availability (AJAX endpoint).
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'subdomain' => 'required|string|max:63',
            'domain_id' => 'required|integer|exists:pteroca_subdomain_domains,id',
        ]);

        $subdomainName = strtolower(trim($request->input('subdomain')));
        $domainId = (int) $request->input('domain_id');

        $minLength = (int) Subdomain::getSetting('min_length', 3);
        $maxLength = (int) Subdomain::getSetting('max_length', 32);

        // Format validation
        if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $subdomainName)) {
            return response()->json([
                'available' => false,
                'message' => __('subdomains::subdomains.subdomain_invalid'),
            ]);
        }

        if (preg_match('/--/', $subdomainName)) {
            return response()->json([
                'available' => false,
                'message' => __('subdomains::subdomains.subdomain_invalid_consecutive'),
            ]);
        }

        if (strlen($subdomainName) < $minLength) {
            return response()->json([
                'available' => false,
                'message' => __('subdomains::subdomains.subdomain_too_short', ['min' => $minLength]),
            ]);
        }

        if (strlen($subdomainName) > $maxLength) {
            return response()->json([
                'available' => false,
                'message' => __('subdomains::subdomains.subdomain_too_long', ['max' => $maxLength]),
            ]);
        }

        // Blacklist check
        if (SubdomainBlacklist::isBlacklisted($subdomainName)) {
            return response()->json([
                'available' => false,
                'message' => __('subdomains::subdomains.subdomain_blacklisted'),
            ]);
        }

        // Database uniqueness check
        if (Subdomain::where('subdomain', $subdomainName)->where('domain_id', $domainId)->exists()) {
            return response()->json([
                'available' => false,
                'message' => __('subdomains::subdomains.subdomain_taken'),
            ]);
        }

        // Cloudflare DNS check
        try {
            $domain = SubdomainDomain::findOrFail($domainId);
            $fullName = $subdomainName . '.' . $domain->domain;
            $existing = $this->cloudflare->recordExists($domain->cloudflare_zone_id, $fullName, 'A');

            if ($existing) {
                return response()->json([
                    'available' => false,
                    'message' => __('subdomains::subdomains.subdomain_taken_cloudflare'),
                ]);
            }
        } catch (CloudflareException $e) {
            // If Cloudflare check fails, still allow (will be caught on creation)
            Log::warning('Cloudflare availability check failed', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'available' => true,
            'message' => __('subdomains::subdomains.subdomain_available'),
        ]);
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    /**
     * Get and authorize the server (ensure it belongs to the current user).
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    private function getAuthorizedServer(int $serverId): object
    {
        // Attempt to load the server from PteroCA's Server model
        $serverModel = $this->resolveServerModel();
        $server = $serverModel::findOrFail($serverId);

        // Authorization: user must own the server
        if ((int) $server->user_id !== (int) auth()->id()) {
            abort(403, __('subdomains::subdomains.permission_denied'));
        }

        return $server;
    }

    /**
     * Resolve the PteroCA Server model class.
     */
    private function resolveServerModel(): string
    {
        // PteroCA typically uses App\Models\Server
        if (class_exists(\App\Models\Server::class)) {
            return \App\Models\Server::class;
        }

        abort(500, __('subdomains::subdomains.server_not_found'));
    }

    /**
     * Extract the server IP from the server model.
     */
    private function getServerIp(object $server): string
    {
        // PteroCA stores server connection info; adapt to actual model structure
        // Common patterns: $server->node->ip, $server->allocation->ip, etc.
        return $server->allocation->ip
            ?? $server->node->ip
            ?? $server->ip
            ?? '0.0.0.0';
    }

    /**
     * Extract the server port from the server model.
     */
    private function getServerPort(object $server): int
    {
        // Common patterns: $server->allocation->port, $server->port
        return (int) ($server->allocation->port
            ?? $server->port
            ?? 25565);
    }

    /**
     * Validate subdomain input from request.
     */
    private function validateSubdomainInput(Request $request): array
    {
        $minLength = (int) Subdomain::getSetting('min_length', 3);
        $maxLength = (int) Subdomain::getSetting('max_length', 32);

        return $request->validate([
            'subdomain' => [
                'required',
                'string',
                "min:{$minLength}",
                "max:{$maxLength}",
                'regex:/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/',
                'not_regex:/--/',
            ],
            'domain_id' => 'required|integer|exists:pteroca_subdomain_domains,id',
        ], [
            'subdomain.required' => __('subdomains::subdomains.subdomain_invalid'),
            'subdomain.min' => __('subdomains::subdomains.subdomain_too_short', ['min' => $minLength]),
            'subdomain.max' => __('subdomains::subdomains.subdomain_too_long', ['max' => $maxLength]),
            'subdomain.regex' => __('subdomains::subdomains.subdomain_invalid'),
            'subdomain.not_regex' => __('subdomains::subdomains.subdomain_invalid_consecutive'),
            'domain_id.required' => __('subdomains::subdomains.invalid_domain'),
            'domain_id.exists' => __('subdomains::subdomains.invalid_domain'),
        ]);
    }
}
