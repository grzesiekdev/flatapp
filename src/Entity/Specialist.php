<?php

namespace App\Entity;

use App\Repository\SpecialistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SpecialistRepository::class)]
class Specialist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $profession = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 12, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gmb = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $note = null;

    #[ORM\ManyToMany(targetEntity: flat::class, inversedBy: 'specialists')]
    private Collection $flats;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    public function __construct()
    {
        $this->flats = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getProfession(): ?string
    {
        return $this->profession;
    }

    public function setProfession(string $profession): self
    {
        $this->profession = $profession;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getGmb(): ?string
    {
        return $this->gmb;
    }

    public function setGmb(?string $gmb): self
    {
        $this->gmb = $gmb;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;

        return $this;
    }

    /**
     * @return Collection<int, flat>
     */
    public function getFlats(): Collection
    {
        return $this->flats;
    }

    public function addFlat(flat $flat): self
    {
        if (!$this->flats->contains($flat)) {
            $this->flats->add($flat);
        }

        return $this;
    }

    public function removeFlat(flat $flat): self
    {
        $this->flats->removeElement($flat);

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }
}
