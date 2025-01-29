<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Il y a déjà un compte avec cet email !')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     * @Assert\NotBlank(message="Le mot de passe ne peut pas être vide.")
     * @Assert\Length(
     *     min=12,
     *     minMessage="Le mot de passe doit contenir au moins {{ limit }} caractères."
     * )
     * @Assert\Regex(
     *     pattern="/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{12,}$/",
     *     message="Le mot de passe doit contenir au moins une lettre majuscule, une lettre minuscule et un chiffre."
     * )
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateEnvoi = null;

    #[ORM\Column(length : 30)]
    private ?string $prenom = null;

    #[ORM\Column(length: 30)]
    private ?string $nom = null;

    #[ORM\OneToMany(targetEntity: Fichier::class, mappedBy: 'user')]
    private Collection $fichiers;

    private ?string $oldPassword = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\JoinTable(name: "user_demande")]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[InverseJoinColumn(name: 'demander_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: 'User', inversedBy: 'demander')]
    private Collection $demander;

    /**
     * @var Collection<int, self>
     */
    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'demander')]
    private Collection $usersDemande;

    /**
     * @var Collection<int, self>
     */
    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'userAccepte')]
    private Collection $accepter;
    #[ORM\JoinTable(name: "user_accepter",
        joinColumns: [new ORM\JoinColumn(name: "user_id", referencedColumnName: "id")],
        inverseJoinColumns: [new ORM\JoinColumn(name: "accepter_id", referencedColumnName: "id")]
    )]
    /**
     * @var Collection<int, self>
     */
    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'accepter')]
    private Collection $userAccepte;

    /**
     * @var Collection<int, Fichier>
     */
    #[ORM\ManyToMany(targetEntity: Fichier::class, mappedBy: 'partageAvec')]
    private Collection $fichiersPartages;

    /**
     * @var Collection<int, Connexion>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Connexion::class, orphanRemoval: true)]
    private Collection $connexions;

    public function __construct()
    {
        $this->fichiers = new ArrayCollection();
        $this->demander = new ArrayCollection();
        $this->usersDemande = new ArrayCollection();
        $this->accepter = new ArrayCollection();
        $this->userAccepte = new ArrayCollection();
        $this->fichiersPartages = new ArrayCollection();
        $this->connexions = new ArrayCollection();
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

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        // Comparer avec l'ancien mot de passe si disponible
        if ($this->oldPassword && password_verify($password, $this->oldPassword)) {
            throw new \Exception("Le nouveau mot de passe doit être différent de l'ancien.");
        }

        $this->oldPassword = $this->password; // Sauvegarder le mot de passe actuel comme ancien mot de passe
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getDateEnvoi(): ?\DateTimeInterface
    {
        return $this->dateEnvoi;
    }

    public function setDateEnvoi(\DateTimeInterface $dateEnvoi): static
    {
        $this->dateEnvoi = $dateEnvoi;
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

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    /**
     * @return Collection<int, Fichier>
     */
    public function getFichiers(): Collection
    {
        return $this->fichiers;
    }

    public function addFichier(Fichier $fichier): static
    {
        if (!$this->fichiers->contains($fichier)) {
            $this->fichiers->add($fichier);
            $fichier->setUser($this);
        }

        return $this;
    }

    public function removeFichier(Fichier $fichier): static
    {
        if ($this->fichiers->removeElement($fichier)) {
            if ($fichier->getUser() === $this) {
                $fichier->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getDemander(): Collection
    {
        return $this->demander;
    }

    public function addDemander(self $demander): static
    {
        if (!$this->demander->contains($demander)) {
            $this->demander->add($demander);
        }

        return $this;
    }

    public function removeDemander(self $demander): static
    {
        $this->demander->removeElement($demander);

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getUsersDemande(): Collection
    {
        return $this->usersDemande;
    }

    public function addUsersDemande(self $usersDemande): static
    {
        if (!$this->usersDemande->contains($usersDemande)) {
            $this->usersDemande->add($usersDemande);
            $usersDemande->addDemander($this);
        }

        return $this;
    }

    public function removeUsersDemande(self $usersDemande): static
    {
        if ($this->usersDemande->removeElement($usersDemande)) {
            $usersDemande->removeDemander($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getAccepter(): Collection
    {
        return $this->accepter;
    }

    public function addAccepter(self $accepter): static
    {
        if (!$this->accepter->contains($accepter)) {
            $this->accepter->add($accepter);
        }

        return $this;
    }

    public function removeAccepter(self $accepter): static
    {
        $this->accepter->removeElement($accepter);

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getUserAccepte(): Collection
    {
        return $this->userAccepte;
    }

    public function addUserAccepte(self $userAccepte): static
    {
        if (!$this->userAccepte->contains($userAccepte)) {
            $this->userAccepte->add($userAccepte);
            $userAccepte->addAccepter($this);
        }

        return $this;
    }

    public function removeUserAccepte(self $userAccepte): static
    {
        if ($this->userAccepte->removeElement($userAccepte)) {
            $userAccepte->removeAccepter($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Fichier>
     */
    public function getFichiersPartages(): Collection
    {
        return $this->fichiersPartages;
    }

    public function addFichiersPartage(Fichier $fichiersPartage): static
    {
        if (!$this->fichiersPartages->contains($fichiersPartage)) {
            $this->fichiersPartages->add($fichiersPartage);
            $fichiersPartage->addPartageAvec($this);
        }

        return $this;
    }

    public function removeFichiersPartage(Fichier $fichiersPartage): static
    {
        if ($this->fichiersPartages->removeElement($fichiersPartage)) {
            $fichiersPartage->removePartageAvec($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Connexion>
     */
    public function getConnexions(): Collection
    {
        return $this->connexions;
    }

    public function addConnexion(Connexion $connexion): static
    {
        if (!$this->connexions->contains($connexion)) {
            $this->connexions->add($connexion);
            $connexion->setUser($this);
        }

        return $this;
    }

    public function removeConnexion(Connexion $connexion): static
    {
        if ($this->connexions->removeElement($connexion)) {
            // set the owning side to null (unless already changed)
            if ($connexion->getUser() === $this) {
                $connexion->setUser(null);
            }
        }

        return $this;
    }
}
