<?php

namespace Plugins\Subdomains;

use App\Core\Service\Plugin\PluginSettingService;
use Psr\Log\LoggerInterface;

/**
 * Bootstrap class for Subdomains plugin initialization.
 */
class Bootstrap
{
    public function __construct(
        private readonly PluginSettingService $pluginSettingService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Initialize the plugin.
     */
    public function initialize(): void
    {
        $this->logger->info('Subdomains plugin: Bootstrap initialization started');

        try {
            $apiToken = $this->pluginSettingService->get('subdomains', 'cloudflare_api_token', '');

            if (empty($apiToken)) {
                $this->logger->warning('Subdomains plugin: Cloudflare API token is not configured. Please set it in Admin > Settings > Plugins.');
            } else {
                $this->logger->info('Subdomains plugin: Cloudflare API token is configured');
            }

            $this->logger->info('Subdomains plugin: Bootstrap initialization completed successfully');
        } catch (\Exception $e) {
            $this->logger->error('Subdomains plugin: Bootstrap initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Cleanup when plugin is disabled.
     */
    public function cleanup(): void
    {
        $this->logger->info('Subdomains plugin: Bootstrap cleanup started');
        // Don't delete user data or settings - just cleanup temporary resources
        $this->logger->info('Subdomains plugin: Bootstrap cleanup completed');
    }
}
