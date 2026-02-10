<?php

namespace App\Entity;

use App\Repository\ProfilMedicalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfilMedicalRepository::class)]
#[ORM\Table(name: "ProfilMedical")]
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
    private ?\DateTimeInterface $date_naissance = null;

    #[ORM\Column(length: 255)]
    private ?string $contact_urgence = null;

    /**
     * @var Collection<int, RendezVous>
     */
    #[ORM\OneToMany(targetEntity: RendezVous::class, mappedBy: 'profil')]
    private Collection $rendezVouses;

    #[ORM\OneToOne(mappedBy: 'profilMedical', cascade: ['persist', 'remove'])]
    private ?DossierClinique $dossierClinique = null;

    public function __construct()
    {
        $this->rendezVouses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    // --- Association: Titulaire (Utilisateur) ---

    public function getTitulaire(): ?Utilisateur
    {
        return $this->titulaire;
    }

    public function setTitulaire(?Utilisateur $titulaire): static
    {
        $this->titulaire = $titulaire;
        return $this;
    }

    // --- Standard Fields ---

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

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->date_naissance;
    }

    public function setDateNaissance(\DateTimeInterface $date_naissance): static
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

    // --- Association: RendezVous (OneToMany) ---

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
            $rendezVouse->setProfil($this);
        }
        return $this;
    }

    public function removeRendezVouse(RendezVous $rendezVouse): static
    {
        if ($this->rendezVouses->removeElement($rendezVouse)) {
            if ($rendezVouse->getProfil() === $this) {
                $rendezVouse->setProfil(null);
            }
        }
        return $this;
    }

    // --- Association: DossierClinique (OneToOne) ---

    public function getDossierClinique(): ?DossierClinique
    {
        return $this->dossierClinique;
    }

    public function setDossierClinique(DossierClinique $dossierClinique): static
    {
        if ($dossierClinique->getProfilMedical() !== $this) {
            $dossierClinique->setProfilMedical($this);
        }
        $this->dossierClinique = $dossierClinique;
        return $this;
    }
    public function getTitulaireId(): ?int
{
    return $this->titulaire ? $this->titulaire->getId() : null;
}
    public function __debugInfo(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            // We intentionally leave out 'titulaire' and 'dossierClinique' to stop the loop
        ];
    }
} // End of class
