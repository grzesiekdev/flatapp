<?php

namespace App\Entity;

use App\Entity\User\Landlord;
use App\Entity\User\Tenant;
use App\Repository\FlatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FlatRepository::class)]
class Flat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $area = null;

    #[ORM\Column]
    private ?int $numberOfRooms = null;

    #[ORM\Column]
    private ?int $rent = null;

    #[ORM\Column(nullable: true)]
    private array $fees = [];

    #[ORM\Column(nullable: true)]
    private ?int $deposit = null;

    #[ORM\Column]
    private array $pictures = [];

    #[ORM\Column(nullable: true)]
    private array $picturesForTenant = [];

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rentAgreement = null;

    #[ORM\Column]
    private array $furnishing = [];

    #[ORM\OneToMany(mappedBy: 'flat_id', targetEntity: Tenant::class)]
    private Collection $tenants;

    #[ORM\ManyToOne(inversedBy: 'flats')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Landlord $landlord = null;

    public function __construct()
    {
        $this->tenants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArea(): ?int
    {
        return $this->area;
    }

    public function setArea(int $area): self
    {
        $this->area = $area;

        return $this;
    }

    public function getNumberOfRooms(): ?int
    {
        return $this->numberOfRooms;
    }

    public function setNumberOfRooms(int $numberOfRooms): self
    {
        $this->numberOfRooms = $numberOfRooms;

        return $this;
    }

    public function getRent(): ?int
    {
        return $this->rent;
    }

    public function setRent(int $rent): self
    {
        $this->rent = $rent;

        return $this;
    }

    public function getFees(): array
    {
        return $this->fees;
    }

    public function setFees(?array $fees): self
    {
        $this->fees = $fees;

        return $this;
    }

    public function getDeposit(): ?int
    {
        return $this->deposit;
    }

    public function setDeposit(?int $deposit): self
    {
        $this->deposit = $deposit;

        return $this;
    }

    public function getPictures(): array
    {
        return $this->pictures;
    }

    public function setPictures(array $pictures): self
    {
        $this->pictures = $pictures;

        return $this;
    }

    public function getPicturesForTenant(): array
    {
        return $this->picturesForTenant;
    }

    public function setPicturesForTenant(?array $picturesForTenant): self
    {
        $this->picturesForTenant = $picturesForTenant;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getRentAgreement(): ?string
    {
        return $this->rentAgreement;
    }

    public function setRentAgreement(?string $rentAgreement): self
    {
        $this->rentAgreement = $rentAgreement;

        return $this;
    }

    public function getFurnishing(): array
    {
        return $this->furnishing;
    }

    public function setFurnishing(array $furnishing): self
    {
        $this->furnishing = $furnishing;

        return $this;
    }

    /**
     * @return Collection<int, Tenant>
     */
    public function getTenants(): Collection
    {
        return $this->tenants;
    }

    public function addTenant(Tenant $tenant): self
    {
        if (!$this->tenants->contains($tenant)) {
            $this->tenants->add($tenant);
            $tenant->setFlatId($this);
        }

        return $this;
    }

    public function removeTenant(Tenant $tenant): self
    {
        if ($this->tenants->removeElement($tenant)) {
            // set the owning side to null (unless already changed)
            if ($tenant->getFlatId() === $this) {
                $tenant->setFlatId(null);
            }
        }

        return $this;
    }

    public function getLandlord(): ?Landlord
    {
        return $this->landlord;
    }

    public function setLandlord(?Landlord $landlord): self
    {
        $this->landlord = $landlord;

        return $this;
    }
}
