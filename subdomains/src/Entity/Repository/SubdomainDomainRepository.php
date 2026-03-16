<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Plugins\Subdomains\Entity\SubdomainDomain;

/**
 * @extends ServiceEntityRepository<SubdomainDomain>
 */
class SubdomainDomainRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubdomainDomain::class);
    }

    /** @return SubdomainDomain[] */
    public function findActive(): array
    {
        return $this->findBy(['isActive' => true], ['isDefault' => 'DESC', 'domain' => 'ASC']);
    }

    public function findDefault(): ?SubdomainDomain
    {
        return $this->findOneBy(['isDefault' => true, 'isActive' => true]);
    }

    public function clearDefaults(): void
    {
        $this->createQueryBuilder('d')
            ->update()
            ->set('d.isDefault', ':false')
            ->setParameter('false', false)
            ->getQuery()
            ->execute();
    }
}
