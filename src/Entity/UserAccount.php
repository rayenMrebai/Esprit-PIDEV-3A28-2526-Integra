<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: "user_account")]
class UserAccount implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", name: "userid")]
    private ?int $userId = null;

    #[ORM\Column(type: "string", length: 255, name: "username")]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 50)]
    private ?string $username = null;

    #[ORM\Column(type: "string", length: 180, unique: true, name: "email")]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(type: "string", length: 255, name: "passwordHash")]
    private ?string $passwordHash = null;

    #[ORM\Column(type: "string", length: 50, name: "role")]
    private ?string $role = 'EMPLOYE';

    #[ORM\Column(type: "boolean", name: "isActive")]
    private ?bool $isActive = true;

    #[ORM\Column(type: "datetime", nullable: true, name: "lastLogin")]
    private ?\DateTimeInterface $lastLogin = null;

    #[ORM\Column(type: "datetime", name: "accountCreatedDate")]
    private ?\DateTimeInterface $accountCreatedDate = null;

    #[ORM\Column(type: "string", length: 50, name: "accountStatus")]
    private ?string $accountStatus = 'ACTIVE';

    #[ORM\Column(type: "string", length: 255, nullable: true, name: "face_image_path")]
    private ?string $faceImagePath = null;

    // --- OneToOne relation with UserSetting (inverse side) ---
    #[ORM\OneToOne(mappedBy: "userAccount", cascade: ["persist", "remove"])]
    private ?UserSetting $userSetting = null;

    public function __construct()
    {
        $this->accountCreatedDate = new \DateTime();
        $this->role = 'EMPLOYE';
    }

    // Getters and setters
    public function getUserId(): ?int { return $this->userId; }
    public function getUsername(): ?string { return $this->username; }
    public function setUsername(string $username): self { $this->username = $username; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    public function getPasswordHash(): ?string { return $this->passwordHash; }
    public function setPasswordHash(string $passwordHash): self { $this->passwordHash = $passwordHash; return $this; }
    public function getRole(): string { return $this->role; }
    public function setRole(string $role): self { $this->role = $role; return $this; }
    public function getIsActive(): ?bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }
    public function getLastLogin(): ?\DateTimeInterface { return $this->lastLogin; }
    public function setLastLogin(?\DateTimeInterface $lastLogin): self { $this->lastLogin = $lastLogin; return $this; }
    public function getAccountCreatedDate(): ?\DateTimeInterface { return $this->accountCreatedDate; }
    public function getAccountStatus(): ?string { return $this->accountStatus; }
    public function setAccountStatus(string $accountStatus): self { $this->accountStatus = $accountStatus; return $this; }
    public function getFaceImagePath(): ?string { return $this->faceImagePath; }
    public function setFaceImagePath(?string $faceImagePath): self { $this->faceImagePath = $faceImagePath; return $this; }

    // UserSetting relation
    public function getUserSetting(): ?UserSetting { return $this->userSetting; }
    public function setUserSetting(?UserSetting $userSetting): self { $this->userSetting = $userSetting; return $this; }

    // Required by UserInterface
    public function getUserIdentifier(): string { return $this->username; }
    public function getRoles(): array
    {
        return match ($this->role) {
            'ADMINISTRATEUR' => ['ROLE_ADMIN', 'ROLE_MANAGER', 'ROLE_USER'],
            'MANAGER'        => ['ROLE_MANAGER', 'ROLE_USER'],
            default          => ['ROLE_USER'],
        };
    }
    public function getPassword(): ?string { return $this->passwordHash; }
    public function getSalt(): ?string { return null; }
    public function eraseCredentials(): void {}
}