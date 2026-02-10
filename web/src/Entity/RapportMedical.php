<?php

namespace App\Entity;

use App\Repository\RapportMedicalRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "RapportMedical")]
#[ORM\Entity(repositoryClass: RapportMedicalRepository::class)]
class RapportMedical
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Relation ManyToOne avec DossierClinique
    #[ORM\ManyToOne(inversedBy: 'rapportsMedicaux')]
    #[ORM\JoinColumn(name: 'dossier_id', referencedColumnName: 'id', nullable: false)]
    private ?DossierClinique $dossierClinique = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contenu = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $conclusion = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $url_pdf = null;

    #[ORM\Column]
    private ?\DateTime $date_creation = null;

    // --- Getters et Setters ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDossierClinique(): ?DossierClinique
    {
        return $this->dossierClinique;
    }

    public function setDossierClinique(?DossierClinique $dossierClinique): static
    {
        $this->dossierClinique = $dossierClinique;
        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getConclusion(): ?string
    {
        return $this->conclusion;
    }

    public function setConclusion(string $conclusion): static
    {
        $this->conclusion = $conclusion;
        return $this;
    }

    public function getUrlPdf(): ?string
    {
        return $this->url_pdf;
    }

    public function setUrlPdf(?string $url_pdf): static
    {
        $this->url_pdf = $url_pdf;
        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTime $date_creation): static
    {
        $this->date_creation = $date_creation;
        return $this;
    }
}
