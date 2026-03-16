<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Service;

use App\Core\Service\Plugin\PluginSettingService;
use Plugins\Subdomains\Exception\CloudflareException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Cloudflare API v4 service for DNS record management.
 */
class CloudflareService
{
    private const BASE_URL = 'https://api.cloudflare.com/client/v4';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly PluginSettingService $pluginSettingService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Test connection to Cloudflare by verifying a zone.
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
     * Create an A record.
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
     */
    public function createSRVRecord(string $zoneId, string $subdomain, string $target, int $port, string $domain, int $ttl = 1): array
    {
        // Cloudflare deprecated service/proto/name inside data (May 2024).
        // Use root-level 'name' with full SRV name: _service._proto.subdomain.domain
        $srvName = '_minecraft._tcp.' . $subdomain . '.' . $domain;
        return $this->request('POST', "/zones/{$zoneId}/dns_records", [
            'type' => 'SRV',
            'name' => $srvName,
            'data' => [
                'priority' => 0,
                'weight' => 5,
                'port' => $port,
                'target' => $target,
            ],
            'ttl' => $ttl,
        ]);
    }

    /**
     * Delete a DNS record.
     */
    public function deleteRecord(string $zoneId, string $recordId): bool
    {
        $this->request('DELETE', "/zones/{$zoneId}/dns_records/{$recordId}");
        return true;
    }

    /**
     * Check if a DNS record exists.
     */
    public function recordExists(string $zoneId, string $name, string $type = 'A'): ?array
    {
        $response = $this->request('GET', "/zones/{$zoneId}/dns_records", [
            'name' => $name,
            'type' => $type,
        ]);

        $records = $response['result'] ?? [];
        return empty($records) ? null : $records[0];
    }

    /**
     * Create both A + SRV records atomically (rollback A if SRV fails).
     * @return array{a_record_id: string, srv_record_id: string|null}
     */
    public function createSubdomainRecords(string $zoneId, string $subdomain, string $domain, string $serverIp, int $serverPort, int $ttl = 1): array
    {
        $fullHostname = $subdomain . '.' . $domain;

        $this->logger->info('Creating subdomain DNS records', [
            'zone_id' => $zoneId, 'subdomain' => $subdomain, 'domain' => $domain,
            'full_hostname' => $fullHostname, 'server_ip' => $serverIp, 'server_port' => $serverPort,
        ]);

        // Create A record (name = full hostname for clarity)
        $aResponse = $this->createARecord($zoneId, $fullHostname, $serverIp, $ttl);
        $aRecordId = $aResponse['result']['id'] ?? null;

        if (!$aRecordId) {
            throw new CloudflareException('Failed to obtain A record ID from Cloudflare response');
        }

        // Create SRV record for Minecraft (_minecraft._tcp.kirkes.thegamedimension.com)
        try {
            $srvResponse = $this->createSRVRecord($zoneId, $subdomain, $fullHostname, $serverPort, $domain, $ttl);
            $srvRecordId = $srvResponse['result']['id'] ?? null;
        } catch (CloudflareException $e) {
            // Rollback: delete the A record
            try {
                $this->deleteRecord($zoneId, $aRecordId);
            } catch (CloudflareException $rollbackError) {
                $this->logger->error('Failed to rollback A record after SRV creation failure', [
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

    /**
     * Delete A + SRV records safely (continues if one fails).
     * @return array{a_record: bool, srv_record: bool, errors: string[]}
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
                $this->logger->warning('Failed to delete A record', ['record_id' => $aRecordId, 'error' => $e->getMessage()]);
            }
        }

        if ($srvRecordId) {
            try {
                $this->deleteRecord($zoneId, $srvRecordId);
                $results['srv_record'] = true;
            } catch (CloudflareException $e) {
                $results['errors'][] = 'SRV record: ' . $e->getMessage();
                $this->logger->warning('Failed to delete SRV record', ['record_id' => $srvRecordId, 'error' => $e->getMessage()]);
            }
        }

        return $results;
    }

    /**
     * Make an HTTP request to the Cloudflare API.
     */
    private function request(string $method, string $endpoint, array $data = []): array
    {
        $apiToken = $this->getApiToken();
        $url = self::BASE_URL . $endpoint;

        try {
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiToken,
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 30,
            ];

            if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'], true)) {
                $options['json'] = $data;
            } elseif (strtoupper($method) === 'GET' && !empty($data)) {
                $options['query'] = $data;
            }

            $response = $this->httpClient->request(strtoupper($method), $url, $options);
            $body = $response->toArray(false);

            if (!($body['success'] ?? false)) {
                $errors = $body['errors'] ?? [];
                $errorMessages = array_map(
                    fn(array $error) => ($error['code'] ?? '?') . ': ' . ($error['message'] ?? 'Unknown error'),
                    $errors
                );
                $message = implode('; ', $errorMessages) ?: 'Unknown Cloudflare API error';

                $this->logger->error('Cloudflare API error', [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'status' => $response->getStatusCode(),
                    'errors' => $errors,
                ]);

                throw new CloudflareException($message, $errors, $response->getStatusCode());
            }

            return $body;
        } catch (CloudflareException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Cloudflare API unexpected error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw new CloudflareException('Unexpected error: ' . $e->getMessage(), [], 0, $e);
        }
    }

    /**
     * Get the decrypted API token from plugin settings.
     */
    private function getApiToken(): string
    {
        $token = $this->pluginSettingService->get('subdomains', 'cloudflare_api_token', '');

        if (empty($token)) {
            throw new CloudflareException('Cloudflare API token is not configured.');
        }

        return $token;
    }
}
