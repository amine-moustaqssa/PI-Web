<?php

namespace App\Entity;

use App\Repository\MedecinRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MedecinRepository::class)]
class Medecin extends Utilisateur
{
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $matricule = null; // Matches 'matricule'

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $tarif_consultation = null; // Matches 'tarif_consultation'

    #[ORM\ManyToOne(inversedBy: 'medecins')]
    // FORCE DOCTRINE TO USE YOUR EXISTING COLUMN 'specialite_id'
    #[ORM\JoinColumn(name: 'specialite_id', referencedColumnName: 'id', nullable: true)]
    private ?Specialite $specialite = null;

    /**
     * This constructor ensures every new Medecin automatically gets the correct role.
     */
    public function __construct()
    {
        // If the parent class (Utilisateur) had a constructor, we would call parent::__construct();

        // Automatically assign the doctor role
        $this->setRoles(['ROLE_MEDECIN']);
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
}
