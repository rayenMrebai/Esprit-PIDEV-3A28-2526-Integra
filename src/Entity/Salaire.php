<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\SalaireRepository;

#[ORM\Entity(repositoryClass: SalaireRepository::class)]
#[ORM\Table(name: 'salaire')]
class Salaire
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

    #[ORM\ManyToOne(targetEntity: UserAccount::class, inversedBy: 'salaires')]
    #[ORM\JoinColumn(name: 'userId', referencedColumnName: 'userId')]
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

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $baseAmount = null;

    public function getBaseAmount(): ?float
    {
        return $this->baseAmount;
    }

    public function setBaseAmount(float $baseAmount): self
    {
        $this->baseAmount = $baseAmount;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?float $bonusAmount = null;

    public function getBonusAmount(): ?float
    {
        return $this->bonusAmount;
    }

    public function setBonusAmount(?float $bonusAmount): self
    {
        $this->bonusAmount = $bonusAmount;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?float $totalAmount = null;

    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(?float $totalAmount): self
    {
        $this->totalAmount = $totalAmount;
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

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $datePaiement = null;

    public function getDatePaiement(): ?\DateTimeInterface
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(?\DateTimeInterface $datePaiement): self
    {
        $this->datePaiement = $datePaiement;
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

    #[ORM\OneToMany(targetEntity: BonusRule::class, mappedBy: 'salaire')]
    private Collection $bonusRules;

    public function __construct()
    {
        $this->bonusRules = new ArrayCollection();
    }

    /**
     * @return Collection<int, BonusRule>
     */
    public function getBonusRules(): Collection
    {
        if (!$this->bonusRules instanceof Collection) {
            $this->bonusRules = new ArrayCollection();
        }
        return $this->bonusRules;
    }

    public function addBonusRule(BonusRule $bonusRule): self
    {
        if (!$this->getBonusRules()->contains($bonusRule)) {
            $this->getBonusRules()->add($bonusRule);
        }
        return $this;
    }

    public function removeBonusRule(BonusRule $bonusRule): self
    {
        $this->getBonusRules()->removeElement($bonusRule);
        return $this;
    }

}
