<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Controller\Admin;

use App\Core\Controller\Panel\AbstractPanelController;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Plugins\Subdomains\Entity\SubdomainDomain;
use Symfony\Component\HttpFoundation\Response;

/**
 * EasyAdmin CrudController for DNS Domain management.
 * Overrides index() to render a custom domain management page.
 */
class DomainCrudController extends AbstractPanelController
{
    public static function getEntityFqcn(): string
    {
        return SubdomainDomain::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud(
            $crud
                ->setEntityLabelInSingular('Domain')
                ->setEntityLabelInPlural('DNS Domains')
                ->setPageTitle('index', 'Domain Management')
        );
    }

    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions(
            $actions->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::DETAIL)
        );
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('domain'),
            TextField::new('cloudflareZoneId', 'Zone ID'),
            BooleanField::new('isDefault', 'Default'),
            BooleanField::new('isActive', 'Active'),
        ];
    }

    /**
     * Override index to render custom domain management page.
     */
    public function index(AdminContext $context)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $domainRepo = $em->getRepository(SubdomainDomain::class);

        return $this->render('@PluginSubdomains/admin/domains.html.twig', [
            'domains' => $domainRepo->findBy([], ['isDefault' => 'DESC', 'domain' => 'ASC']),
        ]);
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'doctrine.orm.entity_manager' => '?' . EntityManagerInterface::class,
        ]);
    }
}
