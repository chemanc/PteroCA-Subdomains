<?php

declare(strict_types=1);

namespace Plugins\Subdomains\EventSubscriber;

use App\Core\Event\Menu\MenuItemsCollectedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use Plugins\Subdomains\Entity\Subdomain;
use Plugins\Subdomains\Entity\SubdomainBlacklist;
use Plugins\Subdomains\Entity\SubdomainDomain;
use Plugins\Subdomains\Entity\SubdomainLog;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds Subdomain menu items to the PteroCA admin navigation.
 * Uses linkToCrud() so pages render within EasyAdmin context (with sidebar).
 */
class MenuEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            MenuItemsCollectedEvent::class => 'onMenuItemsCollected',
        ];
    }

    public function onMenuItemsCollected(MenuItemsCollectedEvent $event): void
    {
        $menuItems = $event->getMenuItems();

        // Dashboard (Subdomain management overview)
        $menuItems['main'][] = MenuItem::linkToCrud(
            'Subdomains',
            'fas fa-globe',
            Subdomain::class
        );

        // Domains management
        $menuItems['main'][] = MenuItem::linkToCrud(
            'DNS Domains',
            'fas fa-server',
            SubdomainDomain::class
        );

        // Blacklist CRUD
        $menuItems['main'][] = MenuItem::linkToCrud(
            'Subdomain Blacklist',
            'fas fa-ban',
            SubdomainBlacklist::class
        );

        // Activity Logs CRUD
        $menuItems['main'][] = MenuItem::linkToCrud(
            'Subdomain Logs',
            'fas fa-history',
            SubdomainLog::class
        );

        $event->setMenuItems($menuItems);
    }
}
