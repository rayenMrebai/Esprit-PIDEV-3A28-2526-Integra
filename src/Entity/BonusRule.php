<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BonusRuleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BonusRuleRepository::class)]
#[ORM\Table(name: 'bonus_rule')]
#[ORM\HasLifecycleCallbacks]
class BonusRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(name: 'nom_regle', type: 'string', length: 100)]
    #[Assert\NotBlank(message: "Le nom est obligatoire")]
    #[Assert\Length(min: 3, max: 100)]
    private ?string $nomRegle = null;

    #[ORM\Column(name: 'percentage', type: 'float', nullable: true)]
    #[Assert\NotBlank(message: "Le pourcentage est obligatoire")]
    #[Assert\Positive(message: "Le pourcentage doit être positif")]
    private ?float $percentage = null;

    #[ORM\Column(name: 'bonus', type: 'decimal', precision: 10, scale: 2, options: ['default' => 0])]
    private string $bonus = '0.00';

    #[ORM\Column(name: 'condition_text', type: 'text', nullable: false)]
    #[Assert\NotBlank(message: "Le texte de condition est obligatoire")]
    #[Assert\Length(min: 25, minMessage: "Le texte doit contenir au moins {{ limit }} caractères")]
    private ?string $conditionText = null;

    #[ORM\Column(
        name: 'status',
        type: 'string',
        columnDefinition: "ENUM('CRÉE', 'ACTIVE') DEFAULT 'CRÉE'"
    )]
    private string $status = 'CRÉE';

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'bonusRules')]
    #[ORM\JoinColumn(name: 'salaire_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Salaire $salaire = null;

    public function __construct()
    {
        $this->createdAt  = new \DateTimeImmutable();
        $this->updatedAt  = new \DateTimeImmutable();
        $this->percentage = 0.0;
        $this->bonus      = '0.00';
        $this->status     = 'CRÉE';
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getNomRegle(): ?string { return $this->nomRegle; }
    public function setNomRegle(string $nomRegle): static { $this->nomRegle = $nomRegle; return $this; }
    public function getPercentage(): ?float { return $this->percentage; }

    public function setPercentage(?float $percentage): static
    {
        $this->percentage = $percentage;

        if ($percentage !== null && $this->salaire !== null) {
            $baseAmount = $this->salaire->getBaseAmount();
            // On vérifie que le montant n'est pas une chaîne vide ou nulle
            if ($baseAmount !== '' && $baseAmount !== '0.00') {
                $this->bonus = number_format((float) $baseAmount * ($percentage / 100), 2, '.', '');
            }
        }

        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getBonus(): string { return $this->bonus; }

    public function setBonus(float|string $bonus): static
    {
        $this->bonus = is_float($bonus) ? number_format($bonus, 2, '.', '') : $bonus;
        return $this;
    }

    public function getConditionText(): ?string { return $this->conditionText; }
    public function setConditionText(?string $conditionText): static { $this->conditionText = $conditionText; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; $this->updatedAt = new \DateTimeImmutable(); return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
    public function getSalaire(): ?Salaire { return $this->salaire; }
    public function setSalaire(?Salaire $salaire): static { $this->salaire = $salaire; return $this; }

    public function recalculateBonus(): void
    {
        if ($this->salaire !== null && $this->percentage !== null) {
            $baseAmount = $this->salaire->getBaseAmount();
            if ($baseAmount !== '' && $baseAmount !== '0.00') {
                $this->bonus = number_format((float) $baseAmount * ($this->percentage / 100), 2, '.', '');
                $this->updatedAt = new \DateTimeImmutable();
            }
        }
    }
}