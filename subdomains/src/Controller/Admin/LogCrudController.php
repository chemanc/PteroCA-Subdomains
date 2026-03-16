<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Controller\Admin;

use App\Core\Controller\Panel\AbstractPanelController;
use App\Core\Service\Crud\PanelCrudService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Plugins\Subdomains\Entity\SubdomainLog;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * EasyAdmin CRUD for activity logs (read-only).
 */
class LogCrudController extends AbstractPanelController
{
    public function __construct(PanelCrudService $panelCrudService, RequestStack $requestStack)
    {
        parent::__construct($panelCrudService, $requestStack);
    }

    public static function getEntityFqcn(): string
    {
        return SubdomainLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Activity Log')
            ->setEntityLabelInPlural('Subdomain Activity Logs')
            ->setPageTitle('index', 'Subdomain Activity Logs')
            ->setPageTitle('detail', 'Log Details')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->setSearchFields(['action', 'ipAddress']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::EDIT)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $a) => $a->setLabel('View'));
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('action', 'Action'),
            IntegerField::new('userId', 'User ID'),
            TextField::new('ipAddress', 'IP Address')->hideOnIndex(),
            TextareaField::new('detailsJson', 'Details')
                ->hideOnIndex()
                ->setHelp('JSON data with action details')
                ->formatValue(fn($value, $entity) => json_encode($entity->getDetails(), JSON_PRETTY_PRINT)),
            DateTimeField::new('createdAt', 'Date')
                ->setFormat('medium', 'short'),
        ];
    }
}
