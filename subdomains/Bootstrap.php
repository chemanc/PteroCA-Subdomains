<?php

namespace Plugins\Subdomains;

use App\Core\Service\Plugin\PluginSettingService;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * Bootstrap class for Subdomains plugin initialization.
 */
class Bootstrap
{
    public function __construct(
        private readonly PluginSettingService $pluginSettingService,
        private readonly LoggerInterface $logger,
        private readonly Connection $connection,
    ) {}

    /**
     * Initialize the plugin.
     */
    public function initialize(): void
    {
        $this->logger->info('Subdomains plugin: Bootstrap initialization started');

        try {
            // Sync version from plugin.json to database
            $this->syncVersion();

            $apiToken = $this->pluginSettingService->get('subdomains', 'cloudflare_api_token', '');

            if (empty($apiToken)) {
                $this->logger->warning('Subdomains plugin: Cloudflare API token is not configured. Please set it in Admin > Settings > Plugins.');
            }

            $this->logger->info('Subdomains plugin: Bootstrap initialization completed successfully');
        } catch (\Exception $e) {
            $this->logger->error('Subdomains plugin: Bootstrap initialization failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cleanup when plugin is disabled.
     */
    public function cleanup(): void
    {
        $this->logger->info('Subdomains plugin: cleanup (settings preserved for re-enable)');
    }

    /**
     * Read version from plugin.json and update the plugin table if different.
     */
    private function syncVersion(): void
    {
        try {
            $pluginJsonPath = __DIR__ . '/plugin.json';
            if (!file_exists($pluginJsonPath)) {
                return;
            }

            $manifest = json_decode(file_get_contents($pluginJsonPath), true);
            $fileVersion = $manifest['version'] ?? null;
            if (!$fileVersion) {
                return;
            }

            $dbVersion = $this->connection->fetchOne(
                'SELECT version FROM plugin WHERE name = ?',
                ['subdomains']
            );

            if ($dbVersion !== false && $dbVersion !== $fileVersion) {
                $this->connection->executeStatement(
                    'UPDATE plugin SET version = ? WHERE name = ?',
                    [$fileVersion, 'subdomains']
                );
                $this->logger->info("Subdomains plugin: version synced {$dbVersion} → {$fileVersion}");
            }
        } catch (\Exception $e) {
            $this->logger->warning('Subdomains plugin: failed to sync version', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
