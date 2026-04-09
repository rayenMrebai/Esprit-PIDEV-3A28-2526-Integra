<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\BonusRuleRepository;

#[ORM\Entity(repositoryClass: BonusRuleRepository::class)]
#[ORM\Table(name: 'bonus_rule')]
class BonusRule
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

    #[ORM\ManyToOne(targetEntity: Salaire::class, inversedBy: 'bonusRules')]
    #[ORM\JoinColumn(name: 'salaryId', referencedColumnName: 'id')]
    private ?Salaire $salaire = null;

    public function getSalaire(): ?Salaire
    {
        return $this->salaire;
    }

    public function setSalaire(?Salaire $salaire): self
    {
        $this->salaire = $salaire;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nomRegle = null;

    public function getNomRegle(): ?string
    {
        return $this->nomRegle;
    }

    public function setNomRegle(string $nomRegle): self
    {
        $this->nomRegle = $nomRegle;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $percentage = null;

    public function getPercentage(): ?float
    {
        return $this->percentage;
    }

    public function setPercentage(float $percentage): self
    {
        $this->percentage = $percentage;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $bonus = null;

    public function getBonus(): ?float
    {
        return $this->bonus;
    }

    public function setBonus(float $bonus): self
    {
        $this->bonus = $bonus;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $condition_text = null;

    public function getCondition_text(): ?string
    {
        return $this->condition_text;
    }

    public function setCondition_text(?string $condition_text): self
    {
        $this->condition_text = $condition_text;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $status = null;

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getConditionText(): ?string
    {
        return $this->condition_text;
    }

    public function setConditionText(?string $condition_text): static
    {
        $this->condition_text = $condition_text;

        return $this;
    }

}
