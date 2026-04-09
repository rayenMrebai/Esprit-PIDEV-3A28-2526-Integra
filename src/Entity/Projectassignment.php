<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Repository\ProjectassignmentRepository;

#[ORM\Entity(repositoryClass: ProjectassignmentRepository::class)]
#[ORM\Table(name: 'projectassignment')]
class Projectassignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', name: 'idAssignment')]
    private ?int $idAssignment = null;

    #[Assert\NotNull(message: "Le projet est obligatoire")]
    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(name: 'projectId', referencedColumnName: 'projectId', nullable: false)]
    private ?Project $project = null;

    #[Assert\NotNull(message: "L'employé est obligatoire")]
    #[ORM\ManyToOne(targetEntity: UserAccount::class)]
    #[ORM\JoinColumn(name: 'employeeId', referencedColumnName: 'userId', nullable: false)]
    private ?UserAccount $userAccount = null;

    #[Assert\NotBlank(message: "Le rôle est obligatoire")]
    #[Assert\Length(min: 2, max: 255, minMessage: "Le rôle doit contenir au moins 2 caractères")]
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private ?string $role = null;

    #[Assert\NotBlank(message: "Le taux d'allocation est obligatoire")]
    #[Assert\Range(
        min: 0,
        max: 100,
        notInRangeMessage: "Le taux d'allocation doit être compris entre 0 et 100"
    )]
    #[ORM\Column(type: 'integer', name: 'allocationRate', nullable: false)]
    private ?int $allocationRate = null;

    #[Assert\NotBlank(message: "La date de début est obligatoire")]
    #[ORM\Column(type: 'date', name: 'assignedFrom', nullable: true)]
    private ?\DateTimeInterface $assignedFrom = null;

    #[Assert\NotBlank(message: "La date de fin est obligatoire")]
    #[ORM\Column(type: 'date', name: 'assignedTo', nullable: true)]
    private ?\DateTimeInterface $assignedTo = null;

    #[Assert\Callback]
    public function validateAssignment(ExecutionContextInterface $context): void
    {
        if ($this->assignedFrom && $this->assignedTo && $this->assignedTo < $this->assignedFrom) {
            $context->buildViolation("La date de fin doit être postérieure à la date de début")
                ->atPath('assignedTo')
                ->addViolation();
        }

        if ($this->project) {
            $projectStart = $this->project->getStartDate();
            $projectEnd   = $this->project->getEndDate();

            if ($projectStart && $this->assignedFrom && $this->assignedFrom < $projectStart) {
                $context->buildViolation("La date de début de l'affectation ne peut pas être antérieure au début du projet ({{ start }})")
                    ->setParameter('{{ start }}', $projectStart->format('d/m/Y'))
                    ->atPath('assignedFrom')
                    ->addViolation();
            }

            if ($projectEnd && $this->assignedTo && $this->assignedTo > $projectEnd) {
                $context->buildViolation("La date de fin de l'affectation ne peut pas dépasser la fin du projet ({{ end }})")
                    ->setParameter('{{ end }}', $projectEnd->format('d/m/Y'))
                    ->atPath('assignedTo')
                    ->addViolation();
            }
        }
    }

    // Getters et Setters
    public function getIdAssignment(): ?int { return $this->idAssignment; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): self { $this->project = $project; return $this; }

    public function getUserAccount(): ?UserAccount { return $this->userAccount; }
    public function setUserAccount(?UserAccount $userAccount): self { $this->userAccount = $userAccount; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(string $role): self { $this->role = $role; return $this; }

    public function getAllocationRate(): ?int { return $this->allocationRate; }
    public function setAllocationRate(int $allocationRate): self { $this->allocationRate = $allocationRate; return $this; }

    public function getAssignedFrom(): ?\DateTimeInterface { return $this->assignedFrom; }
    public function setAssignedFrom(?\DateTimeInterface $assignedFrom): self { $this->assignedFrom = $assignedFrom; return $this; }

    public function getAssignedTo(): ?\DateTimeInterface { return $this->assignedTo; }
    public function setAssignedTo(?\DateTimeInterface $assignedTo): self { $this->assignedTo = $assignedTo; return $this; }
}