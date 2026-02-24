<?php

namespace App\Entity;

use App\Repository\DisponibiliteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DisponibiliteRepository::class)]
#[ORM\Table(name: 'Disponibilite')]
class Disponibilite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // 1 = Monday, 7 = Sunday
    #[ORM\Column]
    #[Assert\NotNull(message: 'Le jour de la semaine est obligatoire.')]
    #[Assert\Range(
        min: 1,
        max: 7,
        notInRangeMessage: 'Le jour doit être compris entre {{ min }} (lundi) et {{ max }} (dimanche).'
    )]
    private ?int $jourSemaine = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Assert\NotNull(message: "L'heure de début est obligatoire.")]
    private ?\DateTimeInterface $heureDebut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Assert\NotNull(message: "L'heure de fin est obligatoire.")]
    private ?\DateTimeInterface $heureFin = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le champ récurrent est obligatoire.')]
    private ?bool $estRecurrent = null;

    #[ORM\ManyToOne(targetEntity: Medecin::class, inversedBy: 'disponibilites')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le médecin est obligatoire.')]
    private ?Utilisateur $medecin = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJourSemaine(): ?int
    {
        return $this->jourSemaine;
    }

    public function setJourSemaine(int $jourSemaine): static
    {
        $this->jourSemaine = $jourSemaine;

        return $this;
    }

    public function getHeureDebut(): ?\DateTimeInterface
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(\DateTimeInterface $heureDebut): static
    {
        $this->heureDebut = $heureDebut;

        return $this;
    }

    public function getHeureFin(): ?\DateTimeInterface
    {
        return $this->heureFin;
    }

    public function setHeureFin(\DateTimeInterface $heureFin): static
    {
        $this->heureFin = $heureFin;

        return $this;
    }

    public function isEstRecurrent(): ?bool
    {
        return $this->estRecurrent;
    }

    public function setEstRecurrent(bool $estRecurrent): static
    {
        $this->estRecurrent = $estRecurrent;

        return $this;
    }

    public function getMedecin(): ?Utilisateur
    {
        return $this->medecin;
    }

    public function setMedecin(?Utilisateur $medecin): static
    {
        $this->medecin = $medecin;

        return $this;
    }

    public function __toString(): string
    {
        // Useful for debugging or dropdowns: "Monday 08:00 - 12:00"
        return $this->jourSemaine . ' (' . $this->heureDebut->format('H:i') . '-' . $this->heureFin->format('H:i') . ')';
    }
}
