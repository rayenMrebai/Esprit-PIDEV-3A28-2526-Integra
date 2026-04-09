<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Quiz_result;
use App\Entity\Skill;
use App\Repository\Training_programRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: Training_programRepository::class)]
class Training_program
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 150)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    #[Assert\Length(
        min: 3,
        max: 150,
        minMessage: "Le titre doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères."
    )]
    private string $title;

    #[ORM\Column(type: "text", nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $description = null;

    #[ORM\Column(type: "integer", nullable: true)]
    #[Assert\Positive(message: "La durée doit être un nombre positif.")]
    #[Assert\Type(type: "integer", message: "La durée doit être un nombre entier.")]
    private ?int $duration = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    #[Assert\Choice(
        choices: ["présentiel", "en ligne", "hybride"],
        message: "Le type doit être: présentiel, en ligne ou hybride."
    )]
    private ?string $type = null;

    #[ORM\Column(type: "date", nullable: true)]
    #[Assert\LessThanOrEqual(propertyPath: "endDate", message: "La date de début doit être antérieure à la date de fin.")]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: "date", nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: "string", length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ["PROGRAMMÉ", "EN COURS", "TERMINÉ", "ANNULÉ"],
        message: "Le statut doit être: PROGRAMMÉ, EN COURS, TERMINÉ ou ANNULÉ."
    )]
    private ?string $status = null;

    #[ORM\OneToMany(mappedBy: "training", targetEntity: Quiz_result::class)]
    private Collection $quizResults;

    // Relation OneToMany avec Skill
    #[ORM\OneToMany(mappedBy: "trainingProgram", targetEntity: Skill::class)]
    #[Assert\Count(
        min: 1,
        minMessage: "Le programme doit avoir au moins une compétence associée."
    )]
    private Collection $skills;

    public function __construct()
    {
        $this->quizResults = new ArrayCollection();
        $this->skills = new ArrayCollection();
    }

    // ──────────────────────────────────────────────────────────────
    // GETTERS & SETTERS
    // ──────────────────────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): self
    {
        $this->duration = $duration;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getQuizResults(): Collection
    {
        return $this->quizResults;
    }

    public function addQuizResult(Quiz_result $quizResult): self
    {
        if (!$this->quizResults->contains($quizResult)) {
            $this->quizResults[] = $quizResult;
            $quizResult->setTraining($this);
        }
        return $this;
    }

    public function removeQuizResult(Quiz_result $quizResult): self
    {
        if ($this->quizResults->removeElement($quizResult)) {
            if ($quizResult->getTraining() === $this) {
                $quizResult->setTraining(null);
            }
        }
        return $this;
    }

    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function addSkill(Skill $skill): self
    {
        if (!$this->skills->contains($skill)) {
            $this->skills[] = $skill;
            $skill->setTrainingProgram($this);
        }
        return $this;
    }

    public function removeSkill(Skill $skill): self
    {
        if ($this->skills->removeElement($skill)) {
            if ($skill->getTrainingProgram() === $this) {
                $skill->setTrainingProgram(null);
            }
        }
        return $this;
    }
}