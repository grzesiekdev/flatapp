<?php

namespace App\Entity\User\Type;

use App\Entity\Flat;
use App\Entity\User\User;
use App\Repository\TenantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TenantRepository::class)]
class Tenant extends User
{

    #[ORM\ManyToOne(inversedBy: 'tenants')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Flat $flat_id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $tenant_since = null;

    public function getFlatId(): ?Flat
    {
        return $this->flat_id;
    }

    public function setFlatId(?Flat $flat_id): self
    {
        $this->flat_id = $flat_id;

        return $this;
    }

    public function getTenantSince(): ?\DateTimeInterface
    {
        return $this->tenant_since;
    }

    public function setTenantSince(?\DateTimeInterface $tenant_since): self
    {
        $this->tenant_since = $tenant_since;

        return $this;
    }
}
