<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Widget;

use App\Core\Contract\Widget\WidgetInterface;
use App\Core\Enum\WidgetContext;
use App\Core\Enum\WidgetPosition;
use Doctrine\ORM\EntityManagerInterface;
use Plugins\Subdomains\Entity\Subdomain;

/**
 * Dashboard widget that injects JS to replace server IPs with subdomain addresses.
 * Renders an invisible script block that queries [data-ip] elements on the page.
 */
class SubdomainAddressWidget implements WidgetInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getName(): string
    {
        return 'subdomain_address_replacer';
    }

    public function getDisplayName(): string
    {
        return 'Subdomain Address Replacer';
    }

    public function getSupportedContexts(): array
    {
        return [WidgetContext::DASHBOARD];
    }

    public function getPosition(): WidgetPosition
    {
        return WidgetPosition::BOTTOM;
    }

    public function getPriority(): int
    {
        return 1; // Low priority, render last
    }

    public function getTemplate(): string
    {
        return '@PluginSubdomains/widget/address_replacer.html.twig';
    }

    public function getData(WidgetContext $context, array $contextData): array
    {
        $user = $contextData['user'] ?? null;
        if (!$user) {
            return ['subdomainMap' => []];
        }

        // Get all active subdomains for this user
        $subdomains = $this->entityManager->getRepository(Subdomain::class)
            ->findBy(['userId' => $user->getId(), 'status' => Subdomain::STATUS_ACTIVE]);

        $map = [];
        foreach ($subdomains as $sub) {
            $map[$sub->getServerId()] = $sub->getFullAddress();
        }

        return ['subdomainMap' => $map];
    }

    public function isVisible(WidgetContext $context, array $contextData): bool
    {
        return $context === WidgetContext::DASHBOARD;
    }

    public function getColumnSize(): int
    {
        return 12;
    }
}
