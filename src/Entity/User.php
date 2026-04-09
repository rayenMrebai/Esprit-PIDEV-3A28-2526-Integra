<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: "user")]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "userid", type: "integer")]
    private ?int $userid = null;

    #[ORM\Column(name: "username", type: "string", length: 50)]
    #[Assert\NotBlank(message: "Le nom d'utilisateur est obligatoire.")]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: "Le nom d'utilisateur doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le nom d'utilisateur ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $username = null;

    #[ORM\Column(name: "email", type: "string", length: 100)]
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas valide.")]
    private ?string $email = null;

    #[ORM\Column(name: "passwordHash", type: "string", length: 255)]
    #[Assert\NotBlank(message: "Le mot de passe est obligatoire.")]
    #[Assert\Length(min: 6, minMessage: "Le mot de passe doit contenir au moins {{ limit }} caractères.")]
    private ?string $passwordHash = null;

    #[ORM\Column(name: "role", type: "string", columnDefinition: "ENUM('ADMINISTRATEUR', 'MANAGER', 'EMPLOYE')")]
    #[Assert\Choice(
        choices: ["ADMINISTRATEUR", "MANAGER", "EMPLOYE"],
        message: "Le rôle doit être: ADMINISTRATEUR, MANAGER ou EMPLOYE."
    )]
    private ?string $role = "EMPLOYE";

    #[ORM\Column(name: "isActive", type: "boolean")]
    private ?bool $isActive = true;

    #[ORM\Column(name: "lastLogin", type: "datetime", nullable: true)]
    private ?\DateTimeInterface $lastLogin = null;

    #[ORM\Column(name: "accountCreatedDate", type: "datetime")]
    private ?\DateTimeInterface $accountCreatedDate = null;

    #[ORM\Column(name: "accountStatus", type: "string", columnDefinition: "ENUM('ACTIVE', 'SUSPENDED', 'DISABLED')", nullable: true)]
    #[Assert\Choice(
        choices: ["ACTIVE", "SUSPENDED", "DISABLED"],
        message: "Le statut doit être: ACTIVE, SUSPENDED ou DISABLED."
    )]
    private ?string $accountStatus = "ACTIVE";

    // Relation ManyToMany avec Skill
    #[ORM\ManyToMany(targetEntity: Skill::class, inversedBy: "users")]
    #[ORM\JoinTable(name: "user_skill")]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "userid")]
    #[ORM\InverseJoinColumn(name: "skill_id", referencedColumnName: "id")]
    private Collection $skills;

    public function __construct()
    {
        $this->skills = new ArrayCollection();
        $this->accountCreatedDate = new \DateTime();
    }

    // ──────────────────────────────────────────────────────────────
    // GETTERS & SETTERS
    // ──────────────────────────────────────────────────────────────

    public function getUserid(): ?int
    {
        return $this->userid;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeInterface $lastLogin): self
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    public function getAccountCreatedDate(): ?\DateTimeInterface
    {
        return $this->accountCreatedDate;
    }

    public function setAccountCreatedDate(?\DateTimeInterface $accountCreatedDate): self
    {
        $this->accountCreatedDate = $accountCreatedDate;
        return $this;
    }

    public function getAccountStatus(): ?string
    {
        return $this->accountStatus;
    }

    public function setAccountStatus(?string $accountStatus): self
    {
        $this->accountStatus = $accountStatus;
        return $this;
    }

    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function addSkill(Skill $skill): self
    {
        if (!$this->skills->contains($skill)) {
            $this->skills[] = $skill;
            $skill->addUser($this);
        }
        return $this;
    }

    public function removeSkill(Skill $skill): self
    {
        if ($this->skills->removeElement($skill)) {
            $skill->removeUser($this);
        }
        return $this;
    }
}