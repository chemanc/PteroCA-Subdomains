<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Plugins\Subdomains\Entity\Repository\SubdomainRepository')]
#[ORM\Table(name: 'plg_sub_subdomains')]
#[ORM\Index(columns: ['server_id'], name: 'idx_server_id')]
#[ORM\Index(columns: ['user_id'], name: 'idx_user_id')]
#[ORM\Index(columns: ['status'], name: 'idx_status')]
#[ORM\UniqueConstraint(name: 'uniq_subdomain_domain', columns: ['subdomain', 'domain_id'])]
class Subdomain
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_ERROR = 'error';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $serverId;

    #[ORM\Column(type: Types::INTEGER)]
    private int $userId;

    #[ORM\Column(type: Types::STRING, length: 63)]
    private string $subdomain;

    #[ORM\ManyToOne(targetEntity: SubdomainDomain::class, inversedBy: 'subdomains')]
    #[ORM\JoinColumn(name: 'domain_id', nullable: false, onDelete: 'RESTRICT')]
    private SubdomainDomain $domain;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $cloudflareARecordId = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $cloudflareSrvRecordId = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastChangedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, SubdomainLog> */
    #[ORM\OneToMany(targetEntity: SubdomainLog::class, mappedBy: 'subdomain')]
    private Collection $logs;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->logs = new ArrayCollection();
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getServerId(): int { return $this->serverId; }
    public function getUserId(): int { return $this->userId; }
    public function getSubdomain(): string { return $this->subdomain; }
    public function getDomain(): SubdomainDomain { return $this->domain; }
    public function getCloudflareARecordId(): ?string { return $this->cloudflareARecordId; }
    public function getCloudflareSrvRecordId(): ?string { return $this->cloudflareSrvRecordId; }
    public function getStatus(): string { return $this->status; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }
    public function getLastChangedAt(): ?\DateTimeImmutable { return $this->lastChangedAt; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    /** @return Collection<int, SubdomainLog> */
    public function getLogs(): Collection { return $this->logs; }

    // Setters
    public function setServerId(int $serverId): self { $this->serverId = $serverId; return $this; }
    public function setUserId(int $userId): self { $this->userId = $userId; return $this; }
    public function setSubdomain(string $subdomain): self { $this->subdomain = $subdomain; return $this; }
    public function setDomain(SubdomainDomain $domain): self { $this->domain = $domain; return $this; }
    public function setCloudflareARecordId(?string $id): self { $this->cloudflareARecordId = $id; return $this; }
    public function setCloudflareSrvRecordId(?string $id): self { $this->cloudflareSrvRecordId = $id; return $this; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function setErrorMessage(?string $msg): self { $this->errorMessage = $msg; return $this; }
    public function setLastChangedAt(?\DateTimeImmutable $dt): self { $this->lastChangedAt = $dt; return $this; }
    public function setUpdatedAt(\DateTimeImmutable $dt): self { $this->updatedAt = $dt; return $this; }

    /**
     * Get the full address (subdomain.domain).
     */
    public function getFullAddress(): string
    {
        return $this->subdomain . '.' . $this->domain->getDomain();
    }

    /**
     * Check if subdomain is on cooldown.
     */
    public function isOnCooldown(int $hours): bool
    {
        if ($hours <= 0 || $this->lastChangedAt === null) {
            return false;
        }

        $endsAt = $this->lastChangedAt->modify("+{$hours} hours");
        return $endsAt > new \DateTimeImmutable();
    }

    /**
     * Get remaining cooldown in human-readable form.
     */
    public function getCooldownRemaining(int $hours): ?string
    {
        if (!$this->isOnCooldown($hours)) {
            return null;
        }

        $endsAt = $this->lastChangedAt->modify("+{$hours} hours");
        $now = new \DateTimeImmutable();
        $diff = $now->diff($endsAt);

        if ($diff->h > 0 || $diff->days > 0) {
            return ($diff->days * 24 + $diff->h) . 'h ' . $diff->i . 'm';
        }

        return $diff->i . 'm';
    }

    public function __toString(): string { return $this->getFullAddress(); }
}
