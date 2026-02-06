<?php

namespace App\Entity;

use App\Repository\ConsultationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConsultationRepository::class)]
#[ORM\Table(name: 'Consultation')]
class Consultation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $rdv_id = null;

    #[ORM\ManyToOne(targetEntity: Medecin::class)]
    #[ORM\JoinColumn(name: 'medecin_id', referencedColumnName: 'id', nullable: false)]
    private ?Medecin $medecin = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date_effectuee = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes_privees = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRdvId(): ?string
    {
        return $this->rdv_id;
    }

    public function setRdvId(string $rdv_id): static
    {
        $this->rdv_id = $rdv_id;

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

    public function getDateEffectuee(): ?\DateTimeImmutable
    {
        return $this->date_effectuee;
    }

    public function setDateEffectuee(\DateTimeImmutable $date_effectuee): static
    {
        $this->date_effectuee = $date_effectuee;

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
        return $this->notes_privees;
    }

    public function setNotesPrivees(?string $notes_privees): static
    {
        $this->notes_privees = $notes_privees;

        return $this;
    }
}
