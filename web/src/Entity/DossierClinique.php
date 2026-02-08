<?php

namespace App\Entity;

use App\Repository\DossierCliniqueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "DossierClinique")]
#[ORM\Entity(repositoryClass: DossierCliniqueRepository::class)]
class DossierClinique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Relation OneToOne avec ProfilMedical
    #[ORM\OneToOne(inversedBy: 'dossierClinique', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: "profil_id", referencedColumnName: "id", nullable: false)]
    private ?ProfilMedical $profilMedical = null;

    #[ORM\Column(type: 'json', nullable: true)]
private array $allergies = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $antecedents = null;

    /**
     * @var Collection<int, RapportMedical>
     */
    #[ORM\OneToMany(targetEntity: RapportMedical::class, mappedBy: 'dossierClinique')]
    private Collection $rapportsMedicaux;

    public function __construct()
    {
        $this->rapportsMedicaux = new ArrayCollection();
    }

    // ------------------------
    // Getters & Setters
    // ------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProfilMedical(): ?ProfilMedical
    {
        return $this->profilMedical;
    }

    public function setProfilMedical(ProfilMedical $profilMedical): static
    {
        $this->profilMedical = $profilMedical;
        return $this;
    }

   public function getAllergies(): array
{
    return $this->allergies ?? [];
}


    public function setAllergies(?array $allergies): self
{
    $this->allergies = $allergies ?? [];
    return $this;
}

    public function getAntecedents(): ?string
    {
        return $this->antecedents;
    }

    public function setAntecedents(?string $antecedents): static
    {
        $this->antecedents = $antecedents;
        return $this;
    }

    /**
     * @return Collection<int, RapportMedical>
     */
    public function getRapportsMedicaux(): Collection
    {
        return $this->rapportsMedicaux;
    }

    public function addRapportsMedicaux(RapportMedical $rapportsMedicaux): static
    {
        if (!$this->rapportsMedicaux->contains($rapportsMedicaux)) {
            $this->rapportsMedicaux->add($rapportsMedicaux);
            $rapportsMedicaux->setDossierClinique($this);
        }

        return $this;
    }

    public function removeRapportsMedicaux(RapportMedical $rapportsMedicaux): static
    {
        if ($this->rapportsMedicaux->removeElement($rapportsMedicaux)) {
            if ($rapportsMedicaux->getDossierClinique() === $this) {
                $rapportsMedicaux->setDossierClinique(null);
            }
        }

        return $this;
    }
}
