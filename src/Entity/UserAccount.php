<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\UserAccountRepository;

#[ORM\Entity(repositoryClass: UserAccountRepository::class)]
#[ORM\Table(name: 'user_account')]
class UserAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', name: 'userId')]
    private ?int $userId = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', name: 'username', nullable: false)]
    private ?string $username = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[ORM\Column(type: 'string', name: 'email', nullable: false, unique: true)]
    private ?string $email = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', name: 'password', nullable: false)]
    private ?string $password = null;

    #[ORM\Column(type: 'string', name: 'role', nullable: false)]
    private ?string $role = null;

    #[ORM\Column(type: 'datetime', name: 'createdAt', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime', name: 'updatedAt', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    // Getters / setters
    public function getUserId(): ?int { return $this->userId; }
    public function setUserId(int $userId): self { $this->userId = $userId; return $this; }

    public function getUsername(): ?string { return $this->username; }
    public function setUsername(string $username): self { $this->username = $username; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(string $role): self { $this->role = $role; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
}