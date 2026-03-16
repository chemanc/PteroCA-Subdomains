<?php

declare(strict_types=1);

namespace Plugins\Subdomains\EventSubscriber;

use App\Core\Event\Menu\MenuItemsCollectedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use Plugins\Subdomains\Entity\SubdomainBlacklist;
use Plugins\Subdomains\Entity\SubdomainLog;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds Subdomain menu items to the PteroCA admin navigation.
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

        // Dashboard link
        $menuItems['main'][] = MenuItem::linkToRoute(
            'Subdomains',
            'fas fa-globe',
            'plugin_subdomains_admin_dashboard'
        );

        // Domains management link
        $menuItems['main'][] = MenuItem::linkToRoute(
            'DNS Domains',
            'fas fa-server',
            'plugin_subdomains_admin_domains'
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
