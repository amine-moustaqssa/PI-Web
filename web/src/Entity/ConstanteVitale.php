<?php

namespace App\Entity;

use App\Repository\ConstanteVitaleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Consultation;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ConstanteVitaleRepository::class)]
#[ORM\Table(name: 'ConstanteVitale')]
class ConstanteVitale
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Mapping vers 'consultation_id' en base de données
    #[ORM\ManyToOne(targetEntity: Consultation::class)]
    #[ORM\JoinColumn(name: "consultation_id", referencedColumnName: "id", nullable: false)]
    #[Assert\NotNull(message: "La consultation est obligatoire.")]
    private ?Consultation $consultation_id = null;

    #[ORM\Column(name: "date_prise", type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_prise = null;

    #[Assert\NotBlank(message: "Le type de la constante est obligatoire")]
    #[Assert\Length(
    max: 50,
    maxMessage: "Le type ne doit pas dépasser {{ limit }} caractères"
    )]
    #[ORM\Column(length: 50)]
    private ?string $type = null;


    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Length(max: 20, maxMessage: "L'unité ne doit pas dépasser {{ limit }} caractères.")]
    private ?string $unite = null;

    #[Assert\NotBlank(message: "La valeur est obligatoire")]
#[Assert\Positive(message: "La valeur doit être positive")]
#[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
private ?string $valeur = null;

    // ---------- Getters & Setters ----------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConsultationId(): ?Consultation
    {
        return $this->consultation_id;
    }

    public function setConsultationId(?Consultation $consultation_id): static
    {
        $this->consultation_id = $consultation_id;
        return $this;
    }

    public function getDatePrise(): ?\DateTimeInterface
    {
        return $this->date_prise;
    }

    public function setDatePrise(?\DateTimeInterface $date_prise): static
    {
        $this->date_prise = $date_prise;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getUnite(): ?string
    {
        return $this->unite;
    }

    public function setUnite(?string $unite): static
    {
        $this->unite = $unite;
        return $this;
    }

    public function getValeur(): ?string
    {
        return $this->valeur;
    }

    public function setValeur(string $valeur): static
    {
        $this->valeur = $valeur;
        return $this;
    }
}