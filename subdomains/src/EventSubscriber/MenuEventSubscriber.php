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
 * Items appear in the ADMINISTRACIÓN section as a collapsible submenu.
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

        // Add as collapsible submenu in admin section (only visible to admins)
        $menuItems['admin'][] = MenuItem::subMenu('Subdomains', 'fas fa-globe')->setSubItems([
            MenuItem::linkToCrud('Dashboard', 'fas fa-chart-bar', Subdomain::class),
            MenuItem::linkToCrud('DNS Domains', 'fas fa-server', SubdomainDomain::class),
            MenuItem::linkToCrud('Blacklist', 'fas fa-ban', SubdomainBlacklist::class),
            MenuItem::linkToCrud('Logs', 'fas fa-history', SubdomainLog::class),
        ]);

        $event->setMenuItems($menuItems);
    }
}
