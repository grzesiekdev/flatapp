<?php

namespace App\Entity\User\Type;

use App\Entity\Flat;
use App\Entity\User\User;
use App\Repository\TenantRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TenantRepository::class)]
class Tenant extends User
{

    #[ORM\ManyToOne(inversedBy: 'tenants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Flat $flat_id = null;

    public function getFlatId(): ?Flat
    {
        return $this->flat_id;
    }

    public function setFlatId(?Flat $flat_id): self
    {
        $this->flat_id = $flat_id;

        return $this;
    }
}
