<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Plugins\Subdomains\Entity\Subdomain;

/**
 * @extends ServiceEntityRepository<Subdomain>
 */
class SubdomainRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subdomain::class);
    }

    public function findByServer(int $serverId): ?Subdomain
    {
        return $this->findOneBy(['serverId' => $serverId]);
    }

    /** @return Subdomain[] */
    public function findByUser(int $userId): array
    {
        return $this->findBy(['userId' => $userId]);
    }

    /** @return Subdomain[] */
    public function findActive(): array
    {
        return $this->findBy(['status' => Subdomain::STATUS_ACTIVE]);
    }

    /** @return Subdomain[] */
    public function findRecent(int $limit = 10): array
    {
        return $this->findBy([], ['createdAt' => 'DESC'], $limit);
    }

    public function existsBySubdomainAndDomain(string $subdomain, int $domainId, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.subdomain = :subdomain')
            ->andWhere('s.domain = :domainId')
            ->setParameter('subdomain', $subdomain)
            ->setParameter('domainId', $domainId);

        if ($excludeId !== null) {
            $qb->andWhere('s.id != :excludeId')->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Get statistics counts.
     * @return array{total: int, active: int, pending: int, suspended: int, error: int, today: int, this_week: int, this_month: int}
     */
    public function getStats(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $result = $conn->fetchAssociative('
            SELECT
                COUNT(*) as total,
                SUM(status = :active) as active,
                SUM(status = :pending) as pending,
                SUM(status = :suspended) as suspended,
                SUM(status = :error) as error_count,
                SUM(DATE(created_at) = CURDATE()) as today,
                SUM(YEARWEEK(created_at) = YEARWEEK(CURDATE())) as this_week,
                SUM(YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())) as this_month
            FROM plg_sub_subdomains
        ', [
            'active' => Subdomain::STATUS_ACTIVE,
            'pending' => Subdomain::STATUS_PENDING,
            'suspended' => Subdomain::STATUS_SUSPENDED,
            'error' => Subdomain::STATUS_ERROR,
        ]);

        return [
            'total' => (int) ($result['total'] ?? 0),
            'active' => (int) ($result['active'] ?? 0),
            'pending' => (int) ($result['pending'] ?? 0),
            'suspended' => (int) ($result['suspended'] ?? 0),
            'error' => (int) ($result['error_count'] ?? 0),
            'today' => (int) ($result['today'] ?? 0),
            'this_week' => (int) ($result['this_week'] ?? 0),
            'this_month' => (int) ($result['this_month'] ?? 0),
        ];
    }
}
