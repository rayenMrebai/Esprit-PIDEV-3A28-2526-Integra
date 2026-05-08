<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SalaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SalaireRepository::class)]
#[ORM\Table(name: 'salaire')]
#[ORM\HasLifecycleCallbacks]
class Salaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'base_amount', type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: "Le salaire de base est obligatoire.")]
    #[Assert\Positive(message: "Le salaire de base doit être supérieur à 0.")]
    private string $baseAmount = '0.00';

    #[ORM\Column(name: 'bonus_amount', type: 'decimal', precision: 10, scale: 2, options: ['default' => 0])]
    private string $bonusAmount = '0.00';

    #[ORM\Column(name: 'total_amount', type: 'decimal', precision: 10, scale: 2)]
    private string $totalAmount = '0.00';

    #[ORM\Column(
        name: 'status',
        type: 'string',
        columnDefinition: "ENUM('CREÉ', 'EN_COURS', 'PAYÉ') DEFAULT 'CREÉ'"
    )]
    #[Assert\NotBlank(message: "Le statut est obligatoire.")]
    #[Assert\Choice(choices: ['CREÉ', 'EN_COURS', 'PAYÉ'], message: "Le statut sélectionné n'est pas valide.")]
    private string $status = 'CREÉ';

    #[ORM\Column(name: 'date_paiement', type: 'date', nullable: true)]
    #[Assert\NotBlank(message: "La date de paiement est obligatoire.")]
    #[Assert\GreaterThanOrEqual("today", message: "La date de paiement ne peut pas être dans le passé.")]
    private ?\DateTimeInterface $datePaiement = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'salaires')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'userid', nullable: false)]
    #[Assert\NotBlank(message: "L'employé est obligatoire.")]
    private ?UserAccount $user = null;

    /** @var Collection<int, BonusRule> */
    #[ORM\OneToMany(mappedBy: 'salaire', targetEntity: BonusRule::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $bonusRules;

    public function __construct()
    {
        $this->bonusRules  = new ArrayCollection();
        $this->createdAt   = new \DateTimeImmutable();
        $this->updatedAt   = new \DateTimeImmutable();
        $this->baseAmount  = '0.00';
        $this->bonusAmount = '0.00';
        $this->totalAmount = '0.00';
        $this->status      = 'CREÉ';
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getBaseAmount(): string { return $this->baseAmount; }
    public function setBaseAmount(float|string $baseAmount): static
    {
        $this->baseAmount = is_float($baseAmount) ? number_format($baseAmount, 2, '.', '') : $baseAmount;
        $this->totalAmount = number_format((float) $this->baseAmount + (float) $this->bonusAmount, 2, '.', '');
        return $this;
    }

    public function getBonusAmount(): string { return $this->bonusAmount; }
    public function setBonusAmount(float|string $bonusAmount): static
    {
        $this->bonusAmount = is_float($bonusAmount) ? number_format($bonusAmount, 2, '.', '') : $bonusAmount;
        return $this;
    }

    public function getTotalAmount(): string { return $this->totalAmount; }
    public function setTotalAmount(float|string $totalAmount): static
    {
        $this->totalAmount = is_float($totalAmount) ? number_format($totalAmount, 2, '.', '') : $totalAmount;
        return $this;
    }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDatePaiement(): ?\DateTimeInterface { return $this->datePaiement; }
    public function setDatePaiement(?\DateTimeInterface $datePaiement): static { $this->datePaiement = $datePaiement; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    public function getUser(): ?UserAccount { return $this->user; }
    public function setUser(?UserAccount $user): static { $this->user = $user; return $this; }

    /** @return Collection<int, BonusRule> */
    public function getBonusRules(): Collection { return $this->bonusRules; }

    public function addBonusRule(BonusRule $bonusRule): static
    {
        if (!$this->bonusRules->contains($bonusRule)) {
            $this->bonusRules->add($bonusRule);
            $bonusRule->setSalaire($this);
        }
        return $this;
    }

    public function removeBonusRule(BonusRule $bonusRule): static
    {
        if ($this->bonusRules->removeElement($bonusRule)) {
            if ($bonusRule->getSalaire() === $this) {
                $bonusRule->setSalaire(null);
            }
        }
        return $this;
    }
}