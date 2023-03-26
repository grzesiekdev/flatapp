<?php

namespace App\Entity\User;

use App\Entity\flat;
use App\Repository\TenantRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TenantRepository::class)]
class Tenant extends User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tenants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Flat $flat_id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

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
