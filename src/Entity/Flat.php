<?php

namespace App\Entity;

use App\Entity\User\Type\Landlord;
use App\Entity\User\Type\Tenant;
use App\Repository\FlatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Entity(repositoryClass: FlatRepository::class)]
class Flat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column]
    private ?int $area = null;

    #[Assert\NotBlank]
    #[ORM\Column]
    private ?int $numberOfRooms = null;

    #[ORM\Column]
    private ?int $rent = null;

    #[ORM\Column(nullable: true)]
    private array $fees = [];

    #[ORM\Column(nullable: true)]
    private ?int $deposit = null;

    #[ORM\Column(nullable: true)]
    private array $pictures = [];

    #[ORM\Column(nullable: true)]
    private array $picturesForTenant = [];

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $description = null;

    #[Assert\NotBlank]
    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rentAgreement = null;

    #[ORM\Column(nullable: true)]
    private array $furnishing = [];

    #[ORM\OneToMany(mappedBy: 'flat_id', targetEntity: Tenant::class)]
    private Collection $tenants;

    #[ORM\ManyToOne(inversedBy: 'flats')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Landlord $landlord = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $additional_furnishing = null;

    #[ORM\Column(length: 255)]
    private ?string $floor = null;

    #[ORM\Column(length: 255)]
    private ?string $max_floor = null;

    #[ORM\Column(type: 'ulid', nullable: true)]
    private ?Ulid $invitationCode = null;

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

    public function getAdditionalFurnishing(): ?string
    {
        return $this->additional_furnishing;
    }

    public function setAdditionalFurnishing(?string $additional_furnishing): self
    {
        $this->additional_furnishing = $additional_furnishing;

        return $this;
    }

    public function getFloor(): ?string
    {
        return $this->floor;
    }

    public function setFloor(string $floor): self
    {
        $this->floor = $floor;

        return $this;
    }

    public function getMaxFloor(): ?string
    {
        return $this->max_floor;
    }

    public function setMaxFloor(string $max_floor): self
    {
        $this->max_floor = $max_floor;

        return $this;
    }

    public function copy(Flat $flat): void
    {
        $this->setArea($flat->getArea());
        $this->setAddress($flat->getAddress());
        $this->setFloor($flat->getFloor());
        $this->setMaxFloor($flat->getMaxFloor());
        $this->setRent($flat->getRent());
        $this->setDeposit($flat->getDeposit());
        $this->setFees($flat->getFees());
        $this->setDescription($flat->getDescription());
        $this->setFurnishing($flat->getFurnishing());
        $this->setAdditionalFurnishing($flat->getAdditionalFurnishing());
    }

    public function getInvitationCode(): ?Ulid
    {
        return $this->invitationCode;
    }

    public function setInvitationCode(?Ulid $invitationCode): self
    {
        $this->invitationCode = $invitationCode;

        return $this;
    }
}
