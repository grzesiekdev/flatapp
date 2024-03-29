<?php

namespace App\Entity\User\Type;

use App\Entity\Flat;
use App\Entity\User\User;
use App\Repository\LandlordRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LandlordRepository::class)]
class Landlord extends User
{
    #[ORM\OneToMany(mappedBy: 'landlord', targetEntity: Flat::class, orphanRemoval: true)]
    private Collection $flats;

    public function __construct()
    {
        $this->flats = new ArrayCollection();
    }

    /**
     * @return Collection<int, Flat>
     */
    public function getFlats(): Collection
    {
        return $this->flats;
    }

    public function addFlat(Flat $flat): self
    {
        if (!$this->flats->contains($flat)) {
            $this->flats->add($flat);
            $flat->setLandlord($this);
        }

        return $this;
    }

    public function removeFlat(Flat $flat): self
    {
        if ($this->flats->removeElement($flat)) {
            // set the owning side to null (unless already changed)
            if ($flat->getLandlord() === $this) {
                $flat->setLandlord(null);
            }
        }

        return $this;
    }
}
