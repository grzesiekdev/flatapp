<?php

namespace App\Entity;

use App\Repository\UtilityMeterReadingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UtilityMeterReadingRepository::class)]
class UtilityMeterReading
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(inversedBy: 'utilityMeterReadings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Flat $flat = null;

    #[ORM\Column(nullable: true)]
    private array $water = [];

    #[ORM\Column(nullable: true)]
    private array $gas = [];

    #[ORM\Column(nullable: true)]
    private array $electricity = [];

    #[ORM\Column(nullable: true)]
    private ?bool $was_edited = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getFlat(): ?Flat
    {
        return $this->flat;
    }

    public function setFlat(?Flat $flat): self
    {
        $this->flat = $flat;

        return $this;
    }

    public function getWater(): array
    {
        return $this->water;
    }

    public function setWater(?array $water): self
    {
        $this->water = $water;

        return $this;
    }

    public function getGas(): array
    {
        return $this->gas;
    }

    public function setGas(?array $gas): self
    {
        $this->gas = $gas;

        return $this;
    }

    public function getElectricity(): array
    {
        return $this->electricity;
    }

    public function setElectricity(?array $electricity): self
    {
        $this->electricity = $electricity;

        return $this;
    }

    public function isWasEdited(): ?bool
    {
        return $this->was_edited;
    }

    public function setWasEdited(?bool $was_edited): self
    {
        $this->was_edited = $was_edited;

        return $this;
    }
}
