<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Controller\Admin;

use App\Core\Controller\Panel\AbstractPanelController;
use App\Core\Service\Crud\PanelCrudService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Plugins\Subdomains\Entity\Subdomain;
use Plugins\Subdomains\Entity\SubdomainDomain;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * EasyAdmin CrudController for Subdomain dashboard.
 * Overrides index() to render a custom dashboard instead of standard CRUD table.
 */
class SubdomainCrudController extends AbstractPanelController
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        PanelCrudService $panelCrudService,
        RequestStack $requestStack,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct($panelCrudService, $requestStack);
        $this->entityManager = $entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return Subdomain::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud(
            $crud
                ->setEntityLabelInSingular('Subdomain')
                ->setEntityLabelInPlural('Subdomain Management')
                ->setPageTitle('index', 'Subdomain Management')
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
            TextField::new('subdomain'),
            TextField::new('status'),
            DateTimeField::new('createdAt'),
        ];
    }

    /**
     * Override index to render custom dashboard instead of CRUD table.
     */
    public function index(AdminContext $context)
    {
        $subRepo = $this->entityManager->getRepository(Subdomain::class);
        $domainRepo = $this->entityManager->getRepository(SubdomainDomain::class);

        return $this->render('@PluginSubdomains/admin/dashboard.html.twig', [
            'stats' => $subRepo->getStats(),
            'recentSubdomains' => $subRepo->findRecent(10),
            'domains' => $domainRepo->findAll(),
        ]);
    }
}
