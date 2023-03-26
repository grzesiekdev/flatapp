<?php

namespace App\Entity\User;

use App\Entity\Flat;
use App\Repository\LandLordRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LandLordRepository::class)]
class LandLord extends User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'landlords')]
    private ?Flat $flats = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFlats(): ?Flat
    {
        return $this->flats;
    }

    public function setFlats(?Flat $flats): self
    {
        $this->flats = $flats;

        return $this;
    }
}
