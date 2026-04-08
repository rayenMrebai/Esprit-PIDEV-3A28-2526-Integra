<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Quiz_result;
use App\Entity\Skill;
use App\Repository\Training_programRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: Training_programRepository::class)]
class Training_program
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 150)]
    private string $title;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $duration = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: "date", nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: "date", nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: "string", length: 20, nullable: true)]
    private ?string $status = null;

    #[ORM\OneToMany(mappedBy: "trainingProgram", targetEntity: Skill::class)]
    private Collection $skills;

    #[ORM\OneToMany(mappedBy: "training", targetEntity: Quiz_result::class)]
    private Collection $quizResults;

    public function __construct()
    {
        $this->skills = new ArrayCollection();
        $this->quizResults = new ArrayCollection();
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

    // ──────────────────────────────────────────────────────────────
    // RELATIONS
    // ──────────────────────────────────────────────────────────────

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
}