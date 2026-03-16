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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Plugins\Subdomains\Entity\SubdomainBlacklist;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * EasyAdmin CRUD for blacklist management.
 */
class BlacklistCrudController extends AbstractPanelController
{
    public function __construct(PanelCrudService $panelCrudService, RequestStack $requestStack)
    {
        parent::__construct($panelCrudService, $requestStack);
    }

    public static function getEntityFqcn(): string
    {
        return SubdomainBlacklist::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Blocked Word')
            ->setEntityLabelInPlural('Subdomain Blacklist')
            ->setPageTitle('index', 'Subdomain Blacklist')
            ->setPageTitle('new', 'Add Blocked Word')
            ->setDefaultSort(['word' => 'ASC'])
            ->setPaginatorPageSize(25)
            ->setSearchFields(['word', 'reason']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::EDIT, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn(Action $a) => $a->setLabel('Remove'))
            ->update(Crud::PAGE_INDEX, Action::NEW, fn(Action $a) => $a->setLabel('Add Word'));
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('word', 'Word / Subdomain')
                ->setHelp('The word or subdomain to block (stored lowercase)'),
            TextField::new('reason', 'Reason')
                ->setRequired(false)
                ->setHelp('Optional reason for blocking'),
            DateTimeField::new('createdAt', 'Added')
                ->hideOnForm()
                ->setFormat('medium', 'short'),
        ];
    }
}
