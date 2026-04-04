<?php

declare(strict_types=1);

namespace Plugins\Subdomains\EventSubscriber;

use App\Core\Event\Menu\MenuItemsCollectedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds Subdomain menu items to the PteroCA admin navigation.
 * Items appear in the ADMINISTRACIÓN section as a collapsible submenu.
 *
 * Uses linkToUrl() instead of linkToCrud() to avoid EasyAdmin's
 * controller resolution during cache compilation, which fails because
 * plugin controllers are registered at runtime, not compile time.
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

        $menuItems['admin'][] = MenuItem::subMenu('Subdomains', 'fas fa-globe')->setSubItems([
            MenuItem::linkToUrl(
                'Dashboard',
                'fas fa-chart-bar',
                '/panel?crudAction=index&crudControllerFqcn=' . urlencode('Plugins\\Subdomains\\Controller\\Admin\\SubdomainCrudController')
            ),
            MenuItem::linkToUrl(
                'DNS Domains',
                'fas fa-server',
                '/panel?crudAction=index&crudControllerFqcn=' . urlencode('Plugins\\Subdomains\\Controller\\Admin\\DomainCrudController')
            ),
            MenuItem::linkToUrl(
                'Blacklist',
                'fas fa-ban',
                '/panel?crudAction=index&crudControllerFqcn=' . urlencode('Plugins\\Subdomains\\Controller\\Admin\\BlacklistCrudController')
            ),
            MenuItem::linkToUrl(
                'Logs',
                'fas fa-history',
                '/panel?crudAction=index&crudControllerFqcn=' . urlencode('Plugins\\Subdomains\\Controller\\Admin\\LogCrudController')
            ),
        ]);

        $event->setMenuItems($menuItems);
    }
}
