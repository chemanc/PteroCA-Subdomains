<?php

namespace App\Observers;

use App\Exceptions\CloudflareException;
use App\Models\Subdomain;
use App\Models\SubdomainLog;
use App\Services\CloudflareService;
use Illuminate\Support\Facades\Log;

class ServerObserver
{
    public function __construct(
        protected CloudflareService $cloudflare
    ) {}

    /**
     * Handle the Server "updated" event.
     *
     * Manages auto-suspend and auto-unsuspend of DNS records
     * when a server's status changes.
     */
    public function updated(object $server): void
    {
        // Only act if status changed
        if (!$server->isDirty('status')) {
            return;
        }

        $subdomain = Subdomain::with('domain')->forServer($server->id)->first();

        if (!$subdomain) {
            return;
        }

        $newStatus = $server->status;
        $autoSuspend = Subdomain::getSetting('auto_suspend_on_suspend', 'true') === 'true';

        // Server suspended -> suspend DNS
        if ($this->isServerSuspended($newStatus) && $autoSuspend) {
            $this->suspendSubdomain($subdomain);
        }

        // Server unsuspended -> restore DNS
        if ($this->isServerActive($newStatus) && $subdomain->status === 'suspended') {
            $this->unsuspendSubdomain($subdomain, $server);
        }
    }

    /**
     * Handle the Server "deleted" event.
     *
     * Removes DNS records and deletes the subdomain when a server is terminated.
     */
    public function deleted(object $server): void
    {
        $autoDelete = Subdomain::getSetting('auto_delete_on_terminate', 'true') === 'true';

        if (!$autoDelete) {
            return;
        }

        $subdomain = Subdomain::with('domain')->forServer($server->id)->first();

        if (!$subdomain) {
            return;
        }

        try {
            $zoneId = $subdomain->domain->cloudflare_zone_id;

            $this->cloudflare->deleteSubdomainRecords(
                $zoneId,
                $subdomain->cloudflare_a_record_id,
                $subdomain->cloudflare_srv_record_id
            );
        } catch (\Exception $e) {
            Log::warning('ServerObserver: Failed to delete DNS records on server termination', [
                'server_id' => $server->id,
                'subdomain_id' => $subdomain->id,
                'error' => $e->getMessage(),
            ]);
        }

        SubdomainLog::log('delete', $subdomain->id, null, [
            'reason' => 'Server terminated (auto-delete)',
            'server_id' => $server->id,
            'subdomain' => $subdomain->subdomain . '.' . ($subdomain->domain->domain ?? ''),
        ]);

        $subdomain->delete();
    }

    // =========================================================================
    // Private Methods
    // =========================================================================

    /**
     * Suspend a subdomain's DNS records.
     */
    private function suspendSubdomain(Subdomain $subdomain): void
    {
        try {
            $zoneId = $subdomain->domain->cloudflare_zone_id;

            $this->cloudflare->deleteSubdomainRecords(
                $zoneId,
                $subdomain->cloudflare_a_record_id,
                $subdomain->cloudflare_srv_record_id
            );

            $subdomain->update(['status' => 'suspended']);

            SubdomainLog::log('suspend', $subdomain->id, null, [
                'reason' => 'Server suspended (auto-suspend)',
                'server_id' => $subdomain->server_id,
            ]);
        } catch (\Exception $e) {
            Log::warning('ServerObserver: Failed to suspend DNS records', [
                'subdomain_id' => $subdomain->id,
                'error' => $e->getMessage(),
            ]);

            $subdomain->update([
                'status' => 'error',
                'error_message' => 'Auto-suspend failed: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Unsuspend a subdomain by recreating DNS records.
     */
    private function unsuspendSubdomain(Subdomain $subdomain, object $server): void
    {
        try {
            $zoneId = $subdomain->domain->cloudflare_zone_id;
            $ttl = (int) Subdomain::getSetting('default_ttl', 1);

            // Get server IP and port
            $serverIp = $server->allocation->ip ?? $server->node->ip ?? $server->ip ?? '0.0.0.0';
            $serverPort = (int) ($server->allocation->port ?? $server->port ?? 25565);

            $records = $this->cloudflare->createSubdomainRecords(
                $zoneId,
                $subdomain->subdomain,
                $subdomain->domain->domain,
                $serverIp,
                $serverPort,
                $ttl
            );

            $subdomain->update([
                'status' => 'active',
                'error_message' => null,
                'cloudflare_a_record_id' => $records['a_record_id'],
                'cloudflare_srv_record_id' => $records['srv_record_id'],
            ]);

            SubdomainLog::log('unsuspend', $subdomain->id, null, [
                'reason' => 'Server unsuspended (auto-restore)',
                'server_id' => $subdomain->server_id,
            ]);
        } catch (\Exception $e) {
            Log::warning('ServerObserver: Failed to unsuspend DNS records', [
                'subdomain_id' => $subdomain->id,
                'error' => $e->getMessage(),
            ]);

            $subdomain->update([
                'status' => 'error',
                'error_message' => 'Auto-unsuspend failed: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if a server status represents suspension.
     */
    private function isServerSuspended(mixed $status): bool
    {
        $suspendedStatuses = ['suspended', 'suspend'];
        return in_array(strtolower((string) $status), $suspendedStatuses, true);
    }

    /**
     * Check if a server status represents active/running.
     */
    private function isServerActive(mixed $status): bool
    {
        $activeStatuses = ['active', 'running', 'unsuspended'];
        return in_array(strtolower((string) $status), $activeStatuses, true);
    }
}
