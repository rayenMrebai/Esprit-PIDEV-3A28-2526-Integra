<?php

namespace App\Entity;

use App\Repository\BonusRuleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BonusRuleRepository::class)]
#[ORM\Table(name: 'bonus_rule')]
class BonusRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'nomRegle', type: 'string', length: 150)]
    private ?string $nomRegle = null;

    #[ORM\Column(name: 'percentage', type: 'float', options: ['default' => 0])]
    private ?float $percentage = 0;

    #[ORM\Column(name: 'bonus', type: 'float', options: ['default' => 0])]
    private ?float $bonus = 0;

    #[ORM\Column(name: 'condition_text', type: 'text', nullable: true)]
    private ?string $conditionText = null;

    #[ORM\Column(
        name: 'status',
        type: 'string',
        columnDefinition: "ENUM('CRÉE', 'ACTIVE') DEFAULT 'CRÉE'"
    )]
    private ?string $status = 'CRÉE';

    #[ORM\Column(name: 'createdAt', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(name: 'updatedAt', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTime $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'bonusRules')]
    #[ORM\JoinColumn(name: 'salaryId', referencedColumnName: 'id', nullable: false)]
    private ?Salaire $salaire = null;

    public function __construct()
    {
        $this->createdAt  = new \DateTime();
        $this->updatedAt  = new \DateTime();
        $this->percentage = 0;
        $this->bonus      = 0;
        $this->status     = 'CRÉE';
    }

    public function getId(): ?int { return $this->id; }

    public function getNomRegle(): ?string { return $this->nomRegle; }
    public function setNomRegle(string $nomRegle): static { $this->nomRegle = $nomRegle; return $this; }

    public function getPercentage(): ?float { return $this->percentage; }
    public function setPercentage(float $percentage): static
    {
        $this->percentage = $percentage;
        if ($this->salaire !== null) {
            $this->bonus = $this->salaire->getBaseAmount() * ($percentage / 100);
        }
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getBonus(): ?float { return $this->bonus; }
    public function setBonus(float $bonus): static { $this->bonus = $bonus; return $this; }

    public function getConditionText(): ?string { return $this->conditionText; }
    public function setConditionText(?string $conditionText): static { $this->conditionText = $conditionText; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; $this->updatedAt = new \DateTime(); return $this; }

    public function getCreatedAt(): ?\DateTime { return $this->createdAt; }
    public function setCreatedAt(\DateTime $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTime { return $this->updatedAt; }
    public function setUpdatedAt(\DateTime $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function getSalaire(): ?Salaire { return $this->salaire; }
    public function setSalaire(?Salaire $salaire): static { $this->salaire = $salaire; return $this; }

    public function recalculateBonus(): void
    {
        if ($this->salaire !== null) {
            $this->bonus     = $this->salaire->getBaseAmount() * ($this->percentage / 100);
            $this->updatedAt = new \DateTime();
        }
    }
}