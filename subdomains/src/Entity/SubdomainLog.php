<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Plugins\Subdomains\Entity\Repository\SubdomainLogRepository')]
#[ORM\Table(name: 'plg_sub_logs')]
#[ORM\Index(columns: ['action'], name: 'idx_action')]
#[ORM\Index(columns: ['created_at'], name: 'idx_log_created_at')]
class SubdomainLog
{
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_SUSPEND = 'suspend';
    public const ACTION_UNSUSPEND = 'unsuspend';
    public const ACTION_ERROR = 'error';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Subdomain::class, inversedBy: 'logs')]
    #[ORM\JoinColumn(name: 'subdomain_id', nullable: true, onDelete: 'SET NULL')]
    private ?Subdomain $subdomain = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $userId = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $action;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $details = null;

    #[ORM\Column(type: Types::STRING, length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getSubdomain(): ?Subdomain { return $this->subdomain; }
    public function setSubdomain(?Subdomain $subdomain): self { $this->subdomain = $subdomain; return $this; }

    public function getUserId(): ?int { return $this->userId; }
    public function setUserId(?int $userId): self { $this->userId = $userId; return $this; }

    public function getAction(): string { return $this->action; }
    public function setAction(string $action): self { $this->action = $action; return $this; }

    public function getDetails(): ?array { return $this->details; }
    public function setDetails(?array $details): self { $this->details = $details; return $this; }

    public function getIpAddress(): ?string { return $this->ipAddress; }
    public function setIpAddress(?string $ipAddress): self { $this->ipAddress = $ipAddress; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /**
     * Get translated action label.
     */
    public function getActionLabel(): string
    {
        return match ($this->action) {
            self::ACTION_CREATE => 'Created',
            self::ACTION_UPDATE => 'Updated',
            self::ACTION_DELETE => 'Deleted',
            self::ACTION_SUSPEND => 'Suspended',
            self::ACTION_UNSUSPEND => 'Unsuspended',
            self::ACTION_ERROR => 'Error',
            default => ucfirst($this->action),
        };
    }

    /**
     * Get CSS badge class for the action.
     */
    public function getActionBadgeClass(): string
    {
        return match ($this->action) {
            self::ACTION_CREATE => 'badge-success',
            self::ACTION_UPDATE => 'badge-info',
            self::ACTION_DELETE, self::ACTION_ERROR => 'badge-danger',
            self::ACTION_SUSPEND => 'badge-warning',
            self::ACTION_UNSUSPEND => 'badge-primary',
            default => 'badge-secondary',
        };
    }
}
