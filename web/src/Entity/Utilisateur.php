<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'Utilisateur')]
#[ORM\InheritanceType("SINGLE_TABLE")]
// 1. We match the exact column name "type_utilisateur" from your DB
#[ORM\DiscriminatorColumn(name: "type_utilisateur", type: "string")]
// 2. We match the exact ENUM values from your DB ('ADMIN', 'TITULAIRE', 'PERSONNEL', 'MEDECIN')
#[ORM\DiscriminatorMap([
    "ADMIN" => Utilisateur::class,
    "TITULAIRE" => Utilisateur::class, // Mapped to self for now (until you create Titulaire.php)
    "PERSONNEL" => Utilisateur::class, // Mapped to self for now (until you create Personnel.php)
    "MEDECIN" => Medecin::class
])]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    // --- MAPPING THE EXISTING DB COLUMNS ---

    // Matches column 'niveau_acces'
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $niveauAcces = null;

    // Matches column 'adresse'
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresse = null;

    // Matches column 'code_postal'
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $codePostal = null;

    /**
     * @var Collection<int, ProfilMedical>
     */
    #[ORM\OneToMany(targetEntity: ProfilMedical::class, mappedBy: 'titulaire')]
    private Collection $profilsMedicaux;

    public function __construct()
    {
        $this->profilsMedicaux = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
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

    public function getNiveauAcces(): ?string
    {
        return $this->niveauAcces;
    }

    public function setNiveauAcces(?string $niveauAcces): static
    {
        $this->niveauAcces = $niveauAcces;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(?string $codePostal): static
    {
        $this->codePostal = $codePostal;
        return $this;
    }

    /**
     * @return Collection<int, ProfilMedical>
     */
    public function getProfilsMedicaux(): Collection
    {
        return $this->profilsMedicaux;
    }

    public function addProfilsMedicaux(ProfilMedical $profilsMedicaux): static
    {
        if (!$this->profilsMedicaux->contains($profilsMedicaux)) {
            $this->profilsMedicaux->add($profilsMedicaux);
            $profilsMedicaux->setTitulaire($this);
        }

        return $this;
    }

    public function removeProfilsMedicaux(ProfilMedical $profilsMedicaux): static
    {
        if ($this->profilsMedicaux->removeElement($profilsMedicaux)) {
            // set the owning side to null (unless already changed)
            if ($profilsMedicaux->getTitulaire() === $this) {
                $profilsMedicaux->setTitulaire(null);
            }
        }

        return $this;
    }
}
