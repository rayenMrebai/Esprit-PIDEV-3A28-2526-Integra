<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Repository\ProjectRepository;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'project')]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', name: 'projectId')]
    private ?int $projectId = null;

    #[Assert\NotBlank(message: "Le nom du projet est obligatoire")]
    #[Assert\Length(min: 3, max: 100, minMessage: "Le nom doit contenir au moins 3 caractères")]
    #[ORM\Column(type: 'string', name: 'name', nullable: false)]
    private ?string $name = null;

    #[Assert\NotBlank(message: "Le budget est obligatoire")]
    #[Assert\Positive(message: "Le budget doit être positif")]
    #[Assert\Type(type: "float", message: "Le budget doit être un nombre valide")]
    #[ORM\Column(type: 'decimal', name: 'budget', nullable: false)]
    private ?float $budget = null;

    #[Assert\NotBlank(message: "La date de début est obligatoire")]
    #[ORM\Column(type: 'date', name: 'startDate', nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[Assert\NotBlank(message: "La date de fin est obligatoire")]
    #[ORM\Column(type: 'date', name: 'endDate', nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: 'text', name: 'description', nullable: true)]
    private ?string $description = null;

    #[Assert\NotBlank(message: "Le statut est obligatoire")]
    #[Assert\Choice(choices: ['PLANNING', 'IN PROGRESS', 'ACTIVE', 'ON HOLD', 'COMPLETED'], message: "Statut invalide")]
    #[ORM\Column(type: 'string', name: 'status', nullable: true)]
    private ?string $status = null;

    #[Assert\Callback]
    public function validateDates(ExecutionContextInterface $context): void
    {
        if ($this->startDate && $this->endDate && $this->endDate < $this->startDate) {
            $context->buildViolation("La date de fin doit être après la date de début")
                ->atPath('endDate')
                ->addViolation();
        }
    }

    // Getters / setters
    public function getProjectId(): ?int { return $this->projectId; }
    public function setProjectId(int $projectId): self { $this->projectId = $projectId; return $this; }

    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): self { $this->name = $name; return $this; }      // ✅ ?string

    public function getBudget(): ?float { return $this->budget; }
    public function setBudget(?float $budget): self { $this->budget = $budget; return $this; }  // ✅ ?float

    public function getStartDate(): ?\DateTimeInterface { return $this->startDate; }
    public function setStartDate(?\DateTimeInterface $startDate): self { $this->startDate = $startDate; return $this; }

    public function getEndDate(): ?\DateTimeInterface { return $this->endDate; }
    public function setEndDate(?\DateTimeInterface $endDate): self { $this->endDate = $endDate; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }  // ✅ ?string

    public function isOverdue(): bool
    {
        if (!$this->endDate) return false;
        $today = new \DateTimeImmutable();
        return $this->endDate < $today && $this->status !== 'COMPLETED';
    }
}