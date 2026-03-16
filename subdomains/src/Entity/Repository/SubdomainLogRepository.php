<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Plugins\Subdomains\Entity\Subdomain;
use Plugins\Subdomains\Entity\SubdomainLog;

/**
 * @extends ServiceEntityRepository<SubdomainLog>
 */
class SubdomainLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubdomainLog::class);
    }

    /**
     * Create a log entry.
     */
    public function log(
        string $action,
        ?Subdomain $subdomain = null,
        ?int $userId = null,
        ?array $details = null,
        ?string $ip = null,
    ): SubdomainLog {
        $entry = new SubdomainLog();
        $entry->setAction($action);
        $entry->setSubdomain($subdomain);
        $entry->setUserId($userId);
        $entry->setDetails($details);
        $entry->setIpAddress($ip);

        $this->getEntityManager()->persist($entry);
        $this->getEntityManager()->flush();

        return $entry;
    }

    /**
     * Find logs with optional filters, ordered by newest first.
     * @return SubdomainLog[]
     */
    public function findFiltered(?string $action = null, ?int $userId = null, ?string $dateFrom = null, ?string $dateTo = null, int $limit = 25, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($action !== null) {
            $qb->andWhere('l.action = :action')->setParameter('action', $action);
        }
        if ($userId !== null) {
            $qb->andWhere('l.userId = :userId')->setParameter('userId', $userId);
        }
        if ($dateFrom !== null) {
            $qb->andWhere('l.createdAt >= :dateFrom')->setParameter('dateFrom', $dateFrom);
        }
        if ($dateTo !== null) {
            $qb->andWhere('l.createdAt <= :dateTo')->setParameter('dateTo', $dateTo . ' 23:59:59');
        }

        return $qb->getQuery()->getResult();
    }

    public function countFiltered(?string $action = null, ?int $userId = null, ?string $dateFrom = null, ?string $dateTo = null): int
    {
        $qb = $this->createQueryBuilder('l')->select('COUNT(l.id)');

        if ($action !== null) {
            $qb->andWhere('l.action = :action')->setParameter('action', $action);
        }
        if ($userId !== null) {
            $qb->andWhere('l.userId = :userId')->setParameter('userId', $userId);
        }
        if ($dateFrom !== null) {
            $qb->andWhere('l.createdAt >= :dateFrom')->setParameter('dateFrom', $dateFrom);
        }
        if ($dateTo !== null) {
            $qb->andWhere('l.createdAt <= :dateTo')->setParameter('dateTo', $dateTo . ' 23:59:59');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Clear all logs.
     */
    public function clearAll(): int
    {
        return $this->createQueryBuilder('l')
            ->delete()
            ->getQuery()
            ->execute();
    }
}
