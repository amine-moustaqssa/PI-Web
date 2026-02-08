<?php

namespace App\Entity;

use App\Repository\ProfilMedicalRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Table(name: "ProfilMedical")]
#[ORM\Entity(repositoryClass: ProfilMedicalRepository::class)]
class ProfilMedical
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'profilsMedicaux')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $titulaire = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date_naissance = null;

    #[ORM\Column(length: 255)]
    private ?string $contact_urgence = null;

    #[ORM\OneToOne(mappedBy: 'profilMedical', cascade: ['persist', 'remove'])]
    private ?DossierClinique $dossierClinique = null;

   
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitulaire(): ?Utilisateur
    {
        return $this->titulaire;
    }

    public function setTitulaire(?Utilisateur $titulaire): static
    {
        $this->titulaire = $titulaire;

        return $this;
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getDateNaissance(): ?\DateTime
    {
        return $this->date_naissance;
    }

    public function setDateNaissance(\DateTime $date_naissance): static
    {
        $this->date_naissance = $date_naissance;

        return $this;
    }

    public function getContactUrgence(): ?string
    {
        return $this->contact_urgence;
    }

    public function setContactUrgence(string $contact_urgence): static
    {
        $this->contact_urgence = $contact_urgence;

        return $this;
    }

    public function getDossierClinique(): ?DossierClinique
    {
        return $this->dossierClinique;
    }

    public function setDossierClinique(DossierClinique $dossierClinique): static
    {
        // set the owning side of the relation if necessary
        if ($dossierClinique->getProfilMedical() !== $this) {
            $dossierClinique->setProfilMedical($this);
        }

        $this->dossierClinique = $dossierClinique;

        return $this;
    }

    

   

    
}
