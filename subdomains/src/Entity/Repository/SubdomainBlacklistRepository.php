<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Plugins\Subdomains\Entity\SubdomainBlacklist;

/**
 * @extends ServiceEntityRepository<SubdomainBlacklist>
 */
class SubdomainBlacklistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubdomainBlacklist::class);
    }

    /**
     * Check if a word is blacklisted (exact match or substring).
     */
    public function isBlacklisted(string $word): bool
    {
        $word = strtolower(trim($word));
        if (empty($word)) {
            return false;
        }

        // Exact match
        if ($this->findOneBy(['word' => $word]) !== null) {
            return true;
        }

        // Check if word contains any blacklisted term
        $allWords = $this->createQueryBuilder('b')
            ->select('b.word')
            ->getQuery()
            ->getSingleColumnResult();

        foreach ($allWords as $blocked) {
            if (str_contains($word, $blocked)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Insert word if it doesn't already exist.
     */
    public function insertIfNotExists(string $word, ?string $reason = null): bool
    {
        $existing = $this->findOneBy(['word' => strtolower(trim($word))]);
        if ($existing !== null) {
            return false;
        }

        $entry = new SubdomainBlacklist();
        $entry->setWord($word);
        $entry->setReason($reason);

        $this->getEntityManager()->persist($entry);
        $this->getEntityManager()->flush();

        return true;
    }

    /**
     * Get default blacklist words.
     * @return string[]
     */
    public static function getDefaultBlacklist(): array
    {
        return [
            'admin', 'administrator', 'api', 'app', 'billing', 'blog', 'cdn', 'cpanel',
            'dashboard', 'dev', 'docs', 'email', 'ftp', 'git', 'help', 'mail',
            'manage', 'mysql', 'ns1', 'ns2', 'panel', 'pop', 'root', 'server',
            'shop', 'smtp', 'ssl', 'staging', 'static', 'store', 'support', 'test',
            'vpn', 'webmail', 'whm', 'www', 'abuse', 'postmaster', 'hostmaster',
            'security', 'noc', 'admin1', 'user', 'users', 'login', 'register',
            'account', 'accounts', 'settings', 'config', 'system', 'sys',
            'localhost', 'local', 'internal', 'private', 'public', 'assets',
            'images', 'img', 'css', 'js', 'fonts', 'media', 'download', 'downloads',
            'upload', 'uploads', 'files', 'file', 'data', 'database', 'db',
            'backup', 'backups', 'temp', 'tmp', 'cache', 'log', 'logs',
            'minecraft', 'mc', 'play', 'game', 'games', 'server1', 'server2',
            'node', 'node1', 'node2', 'wings', 'pterodactyl', 'pteroca',
        ];
    }
}
