<?php

namespace App\Entity;

use App\Repository\MedecinRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MedecinRepository::class)]
class Medecin extends Utilisateur
{
    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'Le matricule doit comporter au moins {{ limit }} caractères.',
        maxMessage: 'Le matricule ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $matricule = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Assert\Positive(message: 'Le tarif de consultation doit être un nombre positif.')]
    private ?string $tarif_consultation = null;

    #[ORM\ManyToOne(inversedBy: 'medecins')]
    // FORCE DOCTRINE TO USE YOUR EXISTING COLUMN 'specialite_id'
    #[ORM\JoinColumn(name: 'specialite_id', referencedColumnName: 'id', nullable: true)]
    private ?Specialite $specialite = null;

    /**
     * @var Collection<int, RendezVous>
     */
    #[ORM\OneToMany(targetEntity: RendezVous::class, mappedBy: 'medecin')]
    private Collection $rendezVouses;

    /**
     * This constructor ensures every new Medecin automatically gets the correct role.
     */
    public function __construct()
    {
        // If the parent class (Utilisateur) had a constructor, we would call parent::__construct();

        // Automatically assign the doctor role
        $this->setRoles(['ROLE_MEDECIN']);
        $this->rendezVouses = new ArrayCollection();
    }

    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    public function setMatricule(?string $matricule): static
    {
        $this->matricule = $matricule;
        return $this;
    }

    public function getTarifConsultation(): ?string
    {
        return $this->tarif_consultation;
    }

    public function setTarifConsultation(?string $tarif_consultation): static
    {
        $this->tarif_consultation = $tarif_consultation;
        return $this;
    }

    public function getSpecialite(): ?Specialite
    {
        return $this->specialite;
    }

    public function setSpecialite(?Specialite $specialite): static
    {
        $this->specialite = $specialite;
        return $this;
    }

    public function __toString(): string
    {
        return $this->getNom() . ' ' . $this->getPrenom();
    }

    /**
     * @return Collection<int, RendezVous>
     */
    public function getRendezVouses(): Collection
    {
        return $this->rendezVouses;
    }

    public function addRendezVouse(RendezVous $rendezVouse): static
    {
        if (!$this->rendezVouses->contains($rendezVouse)) {
            $this->rendezVouses->add($rendezVouse);
            $rendezVouse->setMedecin($this);
        }

        return $this;
    }

    public function removeRendezVouse(RendezVous $rendezVouse): static
    {
        if ($this->rendezVouses->removeElement($rendezVouse)) {
            // set the owning side to null (unless already changed)
            if ($rendezVouse->getMedecin() === $this) {
                $rendezVouse->setMedecin(null);
            }
        }

        return $this;
    }
}
