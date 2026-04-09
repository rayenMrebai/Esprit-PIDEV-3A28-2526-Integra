<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\PasswordResetTokenRepository;

#[ORM\Entity(repositoryClass: PasswordResetTokenRepository::class)]
#[ORM\Table(name: 'password_reset_token')]
class PasswordResetToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: UserAccount::class, inversedBy: 'passwordResetTokens')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'userId')]
    private ?UserAccount $userAccount = null;

    public function getUserAccount(): ?UserAccount
    {
        return $this->userAccount;
    }

    public function setUserAccount(?UserAccount $userAccount): self
    {
        $this->userAccount = $userAccount;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $token = null;

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $expiry_date = null;

    public function getExpiry_date(): ?\DateTimeInterface
    {
        return $this->expiry_date;
    }

    public function setExpiry_date(\DateTimeInterface $expiry_date): self
    {
        $this->expiry_date = $expiry_date;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $used = null;

    public function isUsed(): ?bool
    {
        return $this->used;
    }

    public function setUsed(?bool $used): self
    {
        $this->used = $used;
        return $this;
    }

    public function getExpiryDate(): ?\DateTime
    {
        return $this->expiry_date;
    }

    public function setExpiryDate(\DateTime $expiry_date): static
    {
        $this->expiry_date = $expiry_date;

        return $this;
    }

}
