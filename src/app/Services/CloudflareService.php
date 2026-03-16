<?php

namespace App\Services;

use App\Exceptions\CloudflareException;
use App\Models\Subdomain;
use App\Models\SubdomainLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareService
{
    /**
     * Cloudflare API v4 base URL.
     */
    protected string $baseUrl = 'https://api.cloudflare.com/client/v4';

    // =========================================================================
    // Public Methods
    // =========================================================================

    /**
     * Test the connection to Cloudflare by verifying a zone.
     *
     * @param string $zoneId The Cloudflare Zone ID to verify
     * @return array Zone details on success
     * @throws CloudflareException
     */
    public function testConnection(string $zoneId): array
    {
        $response = $this->request('GET', "/zones/{$zoneId}");

        return [
            'success' => true,
            'zone_name' => $response['result']['name'] ?? 'Unknown',
            'zone_status' => $response['result']['status'] ?? 'Unknown',
            'name_servers' => $response['result']['name_servers'] ?? [],
        ];
    }

    /**
     * Create an A record pointing to the server IP.
     *
     * @param string $zoneId    Cloudflare Zone ID
     * @param string $name      Subdomain name (e.g., "myserver")
     * @param string $ip        Server IP address
     * @param int    $ttl       TTL in seconds (1 = Auto)
     * @return array Cloudflare API response with record ID
     * @throws CloudflareException
     */
    public function createARecord(string $zoneId, string $name, string $ip, int $ttl = 1): array
    {
        return $this->request('POST', "/zones/{$zoneId}/dns_records", [
            'type' => 'A',
            'name' => $name,
            'content' => $ip,
            'ttl' => $ttl,
            'proxied' => false,
        ]);
    }

    /**
     * Create an SRV record for Minecraft server discovery.
     *
     * Creates a record like: _minecraft._tcp.myserver -> myserver.domain.com:25565
     *
     * @param string $zoneId    Cloudflare Zone ID
     * @param string $name      Subdomain name (e.g., "myserver")
     * @param string $target    Full target hostname (e.g., "myserver.thegamedimension.com")
     * @param int    $port      Server port (e.g., 25565)
     * @param string $domain    Root domain (e.g., "thegamedimension.com")
     * @param int    $ttl       TTL in seconds (1 = Auto)
     * @return array Cloudflare API response with record ID
     * @throws CloudflareException
     */
    public function createSRVRecord(
        string $zoneId,
        string $name,
        string $target,
        int $port,
        string $domain,
        int $ttl = 1
    ): array {
        return $this->request('POST', "/zones/{$zoneId}/dns_records", [
            'type' => 'SRV',
            'data' => [
                'service' => '_minecraft',
                'proto' => '_tcp',
                'name' => $name,
                'priority' => 0,
                'weight' => 5,
                'port' => $port,
                'target' => $target,
            ],
            'ttl' => $ttl,
        ]);
    }

    /**
     * Update an existing DNS record.
     *
     * @param string $zoneId   Cloudflare Zone ID
     * @param string $recordId The DNS record ID to update
     * @param array  $data     Fields to update
     * @return array Cloudflare API response
     * @throws CloudflareException
     */
    public function updateRecord(string $zoneId, string $recordId, array $data): array
    {
        return $this->request('PATCH', "/zones/{$zoneId}/dns_records/{$recordId}", $data);
    }

    /**
     * Delete a DNS record.
     *
     * @param string $zoneId   Cloudflare Zone ID
     * @param string $recordId The DNS record ID to delete
     * @return bool True on successful deletion
     * @throws CloudflareException
     */
    public function deleteRecord(string $zoneId, string $recordId): bool
    {
        $this->request('DELETE', "/zones/{$zoneId}/dns_records/{$recordId}");
        return true;
    }

    /**
     * Check if a DNS record already exists in Cloudflare.
     *
     * @param string $zoneId Cloudflare Zone ID
     * @param string $name   Full record name (e.g., "myserver.thegamedimension.com")
     * @param string $type   Record type (A, SRV, etc.)
     * @return array|null Record data if found, null if not
     * @throws CloudflareException
     */
    public function recordExists(string $zoneId, string $name, string $type = 'A'): ?array
    {
        $response = $this->request('GET', "/zones/{$zoneId}/dns_records", [
            'name' => $name,
            'type' => $type,
        ]);

        $records = $response['result'] ?? [];

        if (empty($records)) {
            return null;
        }

        return $records[0];
    }

    /**
     * List DNS records in a zone with optional filters.
     *
     * @param string $zoneId Cloudflare Zone ID
     * @param array  $params Query parameters (type, name, page, per_page, etc.)
     * @return array List of DNS records
     * @throws CloudflareException
     */
    public function listRecords(string $zoneId, array $params = []): array
    {
        return $this->request('GET', "/zones/{$zoneId}/dns_records", $params);
    }

    /**
     * Delete all DNS records associated with a subdomain (A + SRV).
     *
     * Safely handles cases where record IDs are null or deletion fails.
     *
     * @param string      $zoneId      Cloudflare Zone ID
     * @param string|null $aRecordId   The A record ID
     * @param string|null $srvRecordId The SRV record ID
     * @return array Summary of deletion results
     */
    public function deleteSubdomainRecords(string $zoneId, ?string $aRecordId, ?string $srvRecordId): array
    {
        $results = ['a_record' => false, 'srv_record' => false, 'errors' => []];

        if ($aRecordId) {
            try {
                $this->deleteRecord($zoneId, $aRecordId);
                $results['a_record'] = true;
            } catch (CloudflareException $e) {
                $results['errors'][] = 'A record: ' . $e->getMessage();
                Log::warning('Failed to delete A record', [
                    'zone_id' => $zoneId,
                    'record_id' => $aRecordId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($srvRecordId) {
            try {
                $this->deleteRecord($zoneId, $srvRecordId);
                $results['srv_record'] = true;
            } catch (CloudflareException $e) {
                $results['errors'][] = 'SRV record: ' . $e->getMessage();
                Log::warning('Failed to delete SRV record', [
                    'zone_id' => $zoneId,
                    'record_id' => $srvRecordId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Create both A and SRV records for a Minecraft subdomain.
     *
     * This is the main method used when a user creates a subdomain.
     *
     * @param string $zoneId    Cloudflare Zone ID
     * @param string $subdomain Subdomain name (e.g., "myserver")
     * @param string $domain    Root domain (e.g., "thegamedimension.com")
     * @param string $serverIp  Server IP address
     * @param int    $serverPort Server port
     * @param int    $ttl       TTL in seconds
     * @return array ['a_record_id' => string, 'srv_record_id' => string]
     * @throws CloudflareException
     */
    public function createSubdomainRecords(
        string $zoneId,
        string $subdomain,
        string $domain,
        string $serverIp,
        int $serverPort,
        int $ttl = 1
    ): array {
        $fullHostname = $subdomain . '.' . $domain;

        // Create A record
        $aResponse = $this->createARecord($zoneId, $subdomain, $serverIp, $ttl);
        $aRecordId = $aResponse['result']['id'] ?? null;

        if (!$aRecordId) {
            throw new CloudflareException('Failed to obtain A record ID from Cloudflare response');
        }

        // Create SRV record
        try {
            $srvResponse = $this->createSRVRecord(
                $zoneId,
                $subdomain,
                $fullHostname,
                $serverPort,
                $domain,
                $ttl
            );
            $srvRecordId = $srvResponse['result']['id'] ?? null;
        } catch (CloudflareException $e) {
            // Rollback: delete the A record if SRV creation fails
            try {
                $this->deleteRecord($zoneId, $aRecordId);
            } catch (CloudflareException $rollbackError) {
                Log::error('Failed to rollback A record after SRV creation failure', [
                    'a_record_id' => $aRecordId,
                    'error' => $rollbackError->getMessage(),
                ]);
            }
            throw $e;
        }

        return [
            'a_record_id' => $aRecordId,
            'srv_record_id' => $srvRecordId,
        ];
    }

    // =========================================================================
    // Private Methods
    // =========================================================================

    /**
     * Make an HTTP request to the Cloudflare API.
     *
     * Handles authentication, retries, timeouts, and error parsing.
     *
     * @param string $method   HTTP method (GET, POST, PATCH, DELETE)
     * @param string $endpoint API endpoint path (e.g., "/zones/{id}/dns_records")
     * @param array  $data     Request body (POST/PATCH) or query params (GET)
     * @return array Parsed JSON response
     * @throws CloudflareException
     */
    protected function request(string $method, string $endpoint, array $data = []): array
    {
        $apiToken = $this->getApiToken();
        $url = $this->baseUrl . $endpoint;

        try {
            $http = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiToken,
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->retry(3, 1000, function (\Exception $exception) {
                    // Only retry on server errors or connection issues
                    if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
                        return true;
                    }
                    if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                        return $exception->response->status() >= 500;
                    }
                    return false;
                });

            $response = match (strtoupper($method)) {
                'GET' => $http->get($url, $data),
                'POST' => $http->post($url, $data),
                'PATCH' => $http->patch($url, $data),
                'PUT' => $http->put($url, $data),
                'DELETE' => $http->delete($url, $data),
                default => throw new CloudflareException("Unsupported HTTP method: {$method}"),
            };

            $body = $response->json() ?? [];

            // Check for Cloudflare API errors
            if (!($body['success'] ?? false)) {
                $errors = $body['errors'] ?? [];
                $errorMessages = array_map(
                    fn(array $error) => ($error['code'] ?? '?') . ': ' . ($error['message'] ?? 'Unknown error'),
                    $errors
                );
                $message = implode('; ', $errorMessages) ?: 'Unknown Cloudflare API error';

                Log::error('Cloudflare API error', [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'errors' => $errors,
                ]);

                throw new CloudflareException($message, $errors, $response->status());
            }

            return $body;
        } catch (CloudflareException $e) {
            throw $e;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Cloudflare API connection failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw new CloudflareException(
                'Could not connect to Cloudflare API: ' . $e->getMessage(),
                [],
                0,
                $e
            );
        } catch (\Exception $e) {
            Log::error('Cloudflare API unexpected error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw new CloudflareException(
                'Unexpected error communicating with Cloudflare: ' . $e->getMessage(),
                [],
                0,
                $e
            );
        }
    }

    /**
     * Get the decrypted Cloudflare API token from settings.
     *
     * @return string The API token
     * @throws CloudflareException If no token is configured
     */
    protected function getApiToken(): string
    {
        $token = Subdomain::getSetting('cloudflare_api_token');

        if (empty($token)) {
            throw new CloudflareException(
                'Cloudflare API token is not configured. Please set it in the admin panel.'
            );
        }

        return $token;
    }
}
