<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\PasswordResetTokenRepository;

#[ORM\Entity(repositoryClass: PasswordResetTokenRepository::class)]
#[ORM\Table(name: 'password_reset_token')]
class PasswordResetToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserAccount::class, inversedBy: 'passwordResetTokens')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'userid')]
    private ?UserAccount $userAccount = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private string $token;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private \DateTimeInterface $expiry_date;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $used = null;

    public function __construct()
    {
        $this->token = '';
        $this->expiry_date = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserAccount(): ?UserAccount
    {
        return $this->userAccount;
    }

    public function setUserAccount(?UserAccount $userAccount): self
    {
        $this->userAccount = $userAccount;
        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function getExpiryDate(): \DateTimeInterface
    {
        return $this->expiry_date;
    }

    public function setExpiryDate(\DateTimeInterface $expiry_date): self
    {
        $this->expiry_date = $expiry_date;
        return $this;
    }

    public function isUsed(): ?bool
    {
        return $this->used;
    }

    public function setUsed(?bool $used): self
    {
        $this->used = $used;
        return $this;
    }
}