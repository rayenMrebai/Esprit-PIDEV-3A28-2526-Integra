<?php

namespace App\Entity;

use App\Repository\UserAccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

##[ORM\Entity(repositoryClass: UserAccountRepository::class)]
#[ORM\Table(name: 'user_account')]
class UserAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'userId', type: 'integer')]
    private ?int $userId = null;

    #[ORM\Column(name: 'username', type: 'string', length: 50)]
    private ?string $username = null;

    #[ORM\Column(name: 'email', type: 'string', length: 100)]
    private ?string $email = null;

    #[ORM\Column(name: 'passwordHash', type: 'string', length: 255)]
    private ?string $passwordHash = null;

    #[ORM\Column(
        name: 'role',
        type: 'string',
        columnDefinition: "ENUM('ADMINISTRATEUR', 'MANAGER', 'EMPLOYE') NOT NULL DEFAULT 'EMPLOYE'"
    )]
    private ?string $role = null;

    #[ORM\Column(name: 'isActive', type: 'boolean', nullable: true, options: ['default' => 1])]
    private ?bool $isActive = true;

    #[ORM\Column(name: 'lastLogin', type: 'datetime', nullable: true)]
    private ?\DateTime $lastLogin = null;

    #[ORM\Column(name: 'accountCreatedDate', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTime $accountCreatedDate = null;

    #[ORM\Column(
        name: 'accountStatus',
        type: 'string',
        nullable: true,
        columnDefinition: "ENUM('ACTIVE', 'SUSPENDED', 'DISABLED') DEFAULT 'ACTIVE'"
    )]
    private ?string $accountStatus = 'ACTIVE';

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Salaire::class)]
    private Collection $salaires;

    public function __construct()
    {
        $this->salaires           = new ArrayCollection();
        $this->accountCreatedDate = new \DateTime();
        $this->isActive           = true;
        $this->accountStatus      = 'ACTIVE';
    }

    // Alias getId() pour que Doctrine reconnaisse la PK
    public function getId(): ?int { return $this->userId; }
    public function getUserId(): ?int { return $this->userId; }

    public function getUsername(): ?string { return $this->username; }
    public function setUsername(string $username): static { $this->username = $username; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getPasswordHash(): ?string { return $this->passwordHash; }
    public function setPasswordHash(string $passwordHash): static { $this->passwordHash = $passwordHash; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(string $role): static { $this->role = $role; return $this; }

    public function isActive(): ?bool { return $this->isActive; }
    public function setIsActive(?bool $isActive): static { $this->isActive = $isActive; return $this; }

    public function getLastLogin(): ?\DateTime { return $this->lastLogin; }
    public function setLastLogin(?\DateTime $lastLogin): static { $this->lastLogin = $lastLogin; return $this; }

    public function getAccountCreatedDate(): ?\DateTime { return $this->accountCreatedDate; }
    public function setAccountCreatedDate(\DateTime $accountCreatedDate): static { $this->accountCreatedDate = $accountCreatedDate; return $this; }

    public function getAccountStatus(): ?string { return $this->accountStatus; }
    public function setAccountStatus(?string $accountStatus): static { $this->accountStatus = $accountStatus; return $this; }

    public function getSalaires(): Collection { return $this->salaires; }
    public function addSalaire(Salaire $salaire): static
    {
        if (!$this->salaires->contains($salaire)) {
            $this->salaires->add($salaire);
            $salaire->setUser($this);
        }
        return $this;
    }
    public function removeSalaire(Salaire $salaire): static
    {
        if ($this->salaires->removeElement($salaire)) {
            if ($salaire->getUser() === $this) {
                $salaire->setUser(null);
            }
        }
        return $this;
    }
}