<?php

namespace App\Entity;

use App\Repository\RendezVousRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RendezVousRepository::class)]
#[ORM\Table(name: "RendezVous")]
class RendezVous
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: 'Veuillez choisir une date.')]
    private ?\DateTimeInterface $date_debut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_fin = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    #[ORM\Column(length: 100)]
    private ?string $type = null;

    #[ORM\Column(length: 60, nullable: true)]
    #[Assert\Length(max: 60, maxMessage: 'Le motif ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $motif = '';

    #[ORM\ManyToOne(targetEntity: ProfilMedical::class, inversedBy: 'rendezVouses')]
    #[ORM\JoinColumn(name: "profil_id", referencedColumnName: "id", nullable: false)]
    #[Assert\NotNull(message: 'Veuillez sélectionner un patient.')]
    private ?ProfilMedical $profil = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->date_debut;
    }

    public function setDateDebut(?\DateTimeInterface $date_debut): static
    {
        $this->date_debut = $date_debut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->date_fin;
    }

    public function setDateFin(?\DateTimeInterface $date_fin): static
    {
        $this->date_fin = $date_fin;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif ?? '';
        return $this;
    }

    public function getProfil(): ?ProfilMedical
    {
        return $this->profil;
    }

    public function setProfil(?ProfilMedical $profil): static
    {
        $this->profil = $profil;
        return $this;
    }
}