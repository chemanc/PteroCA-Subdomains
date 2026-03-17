<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Twig;

use App\Core\Service\Plugin\PluginSettingService;
use Doctrine\ORM\EntityManagerInterface;
use Plugins\Subdomains\Entity\Subdomain;
use Plugins\Subdomains\Entity\SubdomainDomain;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension that provides subdomain data to templates.
 */
class SubdomainExtension extends AbstractExtension
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PluginSettingService $pluginSettingService,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_subdomain_for_server', [$this, 'getSubdomainForServer']),
            new TwigFunction('get_subdomain_domains', [$this, 'getActiveDomains']),
            new TwigFunction('get_subdomain_setting', [$this, 'getSetting']),
        ];
    }

    public function getSubdomainForServer(int $serverId): ?Subdomain
    {
        return $this->entityManager->getRepository(Subdomain::class)->findByServer($serverId);
    }

    public function getActiveDomains(): array
    {
        return $this->entityManager->getRepository(SubdomainDomain::class)->findActive();
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->pluginSettingService->get('subdomains', $key, $default);
    }
}
