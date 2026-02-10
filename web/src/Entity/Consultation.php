<?php

namespace App\Entity;

use App\Repository\ConsultationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: ConsultationRepository::class)]
#[ORM\Table(name: 'Consultation')]
class Consultation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEffectuee = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire')]
    #[Assert\Choice(
        choices: ['en cours', 'planifié', 'terminé'],
        message: "Le statut doit être : 'en cours', 'planifié' ou 'terminé'"
    )]
    private ?string $statut = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: 'Les notes sont obligatoires')]
    #[Assert\Length(max: 5000, maxMessage: 'Les notes ne doivent pas dépasser {{ limit }} caractères.')]
    private ?string $notesPrivees = null;

    #[ORM\Column(nullable: true)]
    private ?int $rdvId = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le medecin_id est obligatoire')]
    private ?Medecin $medecin = null;

    // ---------------- GETTERS / SETTERS ----------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateEffectuee(): ?\DateTimeInterface
    {
        return $this->dateEffectuee;
    }

    public function setDateEffectuee(?\DateTimeInterface $dateEffectuee): static
    {
        $this->dateEffectuee = $dateEffectuee;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getNotesPrivees(): ?string
    {
        return $this->notesPrivees;
    }

    public function setNotesPrivees(?string $notesPrivees): static
    {
        $this->notesPrivees = $notesPrivees;
        return $this;
    }

    public function getRdvId(): ?int
    {
        return $this->rdvId;
    }

    public function setRdvId(?int $rdvId): static
    {
        $this->rdvId = $rdvId;
        return $this;
    }

    public function getMedecin(): ?Medecin
    {
        return $this->medecin;
    }

    public function setMedecin(?Medecin $medecin): static
    {
        $this->medecin = $medecin;
        return $this;
    }
}
