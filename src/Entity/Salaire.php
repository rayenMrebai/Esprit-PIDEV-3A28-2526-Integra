<?php

namespace App\Entity;

use App\Repository\SalaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SalaireRepository::class)]
#[ORM\Table(name: 'salaire')]
class Salaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'baseAmount', type: 'float')]
    #[Assert\NotBlank(message: "Le salaire de base est obligatoire.")]
    #[Assert\Positive(message: "Le salaire de base doit être supérieur à 0.")]
    private ?float $baseAmount = null;

    #[ORM\Column(name: 'bonusAmount', type: 'float', nullable: true, options: ['default' => 0])]
    private ?float $bonusAmount = 0;

    #[ORM\Column(name: 'totalAmount', type: 'float')]
    private ?float $totalAmount = null;

    #[ORM\Column(
        name: 'status',
        type: 'string',
        columnDefinition: "ENUM('CREÉ', 'EN_COURS', 'PAYÉ') DEFAULT 'CREÉ'"
    )]
    #[Assert\NotBlank(message: "Le statut est obligatoire.")]
    #[Assert\Choice(choices: ['CREÉ', 'EN_COURS', 'PAYÉ'], message: "Le statut sélectionné n'est pas valide.")]
    private ?string $status = 'CREÉ';

    #[ORM\Column(name: 'datePaiement', type: 'date', nullable: true)]
    #[Assert\NotBlank(message: "La date de paiement est obligatoire.")]
    #[Assert\GreaterThanOrEqual("today", message: "La date de paiement ne peut pas être dans le passé.")]
    private ?\DateTimeInterface $datePaiement = null;

    #[ORM\Column(name: 'createdAt', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(name: 'updatedAt', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTime $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'salaires')]
    #[ORM\JoinColumn(name: 'userId', referencedColumnName: 'userId', nullable: false)]
    #[Assert\NotBlank(message: "L'employé est obligatoire.")]
    private ?UserAccount $user = null;

    #[ORM\OneToMany(mappedBy: 'salaire', targetEntity: BonusRule::class, cascade: ['persist', 'remove'])]
    private Collection $bonusRules;

    public function __construct()
    {
        $this->bonusRules  = new ArrayCollection();
        $this->createdAt   = new \DateTime();
        $this->updatedAt   = new \DateTime();
        $this->bonusAmount = 0;
        $this->status      = 'CREÉ';
    }

    public function getId(): ?int { return $this->id; }

    public function getBaseAmount(): ?float { return $this->baseAmount; }
    public function setBaseAmount(float $baseAmount): static
    {
        $this->baseAmount  = $baseAmount;
        $this->totalAmount = $baseAmount + ($this->bonusAmount ?? 0);
        return $this;
    }

    public function getBonusAmount(): ?float { return $this->bonusAmount; }
    public function setBonusAmount(float $bonusAmount): static { $this->bonusAmount = $bonusAmount; return $this; }

    public function getTotalAmount(): ?float { return $this->totalAmount; }
    public function setTotalAmount(float $totalAmount): static { $this->totalAmount = $totalAmount; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; $this->updatedAt = new \DateTime(); return $this; }

    public function getDatePaiement(): ?\DateTimeInterface { return $this->datePaiement; }
    public function setDatePaiement(?\DateTimeInterface $datePaiement): static { $this->datePaiement = $datePaiement; return $this; }

    public function getCreatedAt(): ?\DateTime { return $this->createdAt; }
    public function setCreatedAt(\DateTime $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTime { return $this->updatedAt; }
    public function setUpdatedAt(\DateTime $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function getUser(): ?UserAccount { return $this->user; }
    public function setUser(?UserAccount $user): static { $this->user = $user; return $this; }

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