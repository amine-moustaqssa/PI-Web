<?php

namespace App\Entity;

use App\Repository\RapportMedicalRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Table(name: "RapportMedical")]
#[ORM\Entity(repositoryClass: RapportMedicalRepository::class)]
#[Vich\Uploadable]
class RapportMedical
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'rapportsMedicaux')]
    #[ORM\JoinColumn(name: 'dossier_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: "Le dossier clinique est obligatoire.")]
    private ?DossierClinique $dossierClinique = null;

    #[ORM\OneToOne(targetEntity: Consultation::class)]
    #[ORM\JoinColumn(name: 'consultation_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Consultation $consultation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        min: 10,
        max: 5000,
        minMessage: "Le contenu doit comporter au moins {{ limit }} caractères.",
        maxMessage: "Le contenu ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $contenu = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        min: 5,
        max: 2000,
        minMessage: "La conclusion doit comporter au moins {{ limit }} caractères.",
        maxMessage: "La conclusion ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $conclusion = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url_pdf = null;

    #[Vich\UploadableField(mapping: 'rapport_medical_pdfs', fileNameProperty: 'url_pdf')]
    #[Assert\File(
        maxSize: '5M',
        mimeTypes: ['application/pdf'],
        mimeTypesMessage: 'Veuillez uploader un fichier PDF valide'
    )]
    private ?File $pdfFile = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $date_creation = null;

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

    public function getConsultation(): ?Consultation
    {
        return $this->consultation;
    }

    public function setConsultation(?Consultation $consultation): static
    {
        $this->consultation = $consultation;
        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(?string $contenu): static
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getConclusion(): ?string
    {
        return $this->conclusion;
    }

    public function setConclusion(?string $conclusion): static
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

    public function setDateCreation(?\DateTime $date_creation): static
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    public function setPdfFile(?File $pdfFile = null): void
    {
        $this->pdfFile = $pdfFile;
        
        // IMPORTANT: On met à jour date_creation au lieu d'ajouter updated_at
        if (null !== $pdfFile) {
            $this->date_creation = new \DateTime();
        }
    }

    public function getPdfFile(): ?File
    {
        return $this->pdfFile;
    }
}