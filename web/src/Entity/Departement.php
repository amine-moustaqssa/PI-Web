<?php

namespace App\Entity;

use App\Repository\DepartementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepartementRepository::class)]
#[ORM\Table(name: 'Departement')] // Forces Capital 'D' for your existing DB
class Departement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $code = null;

    #[ORM\OneToMany(mappedBy: 'departement', targetEntity: Specialite::class)]
    private Collection $specialites;

    public function __construct()
    {
        $this->specialites = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return Collection<int, Specialite>
     */
    public function getSpecialites(): Collection
    {
        return $this->specialites;
    }

    public function addSpecialite(Specialite $specialite): static
    {
        if (!$this->specialites->contains($specialite)) {
            $this->specialites->add($specialite);
            $specialite->setDepartement($this);
        }
        return $this;
    }

    public function removeSpecialite(Specialite $specialite): static
    {
        if ($this->specialites->removeElement($specialite)) {
            if ($specialite->getDepartement() === $this) {
                $specialite->setDepartement(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->nom;
    }
}