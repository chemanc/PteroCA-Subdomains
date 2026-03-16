<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Plugins\Subdomains\Entity\Repository\SubdomainDomainRepository')]
#[ORM\Table(name: 'plg_sub_domains')]
class SubdomainDomain
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private string $domain;

    #[ORM\Column(type: Types::STRING, length: 64)]
    private string $cloudflareZoneId;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isDefault = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, Subdomain> */
    #[ORM\OneToMany(targetEntity: Subdomain::class, mappedBy: 'domain')]
    private Collection $subdomains;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->subdomains = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getDomain(): string { return $this->domain; }
    public function setDomain(string $domain): self { $this->domain = $domain; return $this; }

    public function getCloudflareZoneId(): string { return $this->cloudflareZoneId; }
    public function setCloudflareZoneId(string $cloudflareZoneId): self { $this->cloudflareZoneId = $cloudflareZoneId; return $this; }

    public function isDefault(): bool { return $this->isDefault; }
    public function setIsDefault(bool $isDefault): self { $this->isDefault = $isDefault; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /** @return Collection<int, Subdomain> */
    public function getSubdomains(): Collection { return $this->subdomains; }

    public function hasActiveSubdomains(): bool
    {
        return $this->subdomains->exists(fn(int $key, Subdomain $s) => $s->getStatus() !== 'error');
    }

    public function __toString(): string { return $this->domain; }
}
