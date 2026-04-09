<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\Quiz_resultRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: Quiz_resultRepository::class)]
class Quiz_result
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "user_id", type: "integer")]
    #[Assert\NotBlank(message: "L'ID utilisateur est obligatoire.")]
    #[Assert\Positive(message: "L'ID utilisateur doit être un nombre positif.")]
    private ?int $userId = null;

    #[ORM\ManyToOne(targetEntity: Training_program::class)]
    #[ORM\JoinColumn(name: "training_id", referencedColumnName: "id")]
    #[Assert\NotNull(message: "La formation est obligatoire.")]
    private ?Training_program $training = null;

    #[ORM\Column(type: "integer")]
    #[Assert\NotBlank(message: "Le score est obligatoire.")]
    #[Assert\Range(
        min: 0,
        max: 100,
        notInRangeMessage: "Le score doit être compris entre {{ min }} et {{ max }}."
    )]
    private ?int $score = null;

    #[ORM\Column(name: "total_questions", type: "integer")]
    #[Assert\NotBlank(message: "Le nombre total de questions est obligatoire.")]
    #[Assert\Positive(message: "Le nombre total de questions doit être positif.")]
    private ?int $totalQuestions = null;

    #[ORM\Column(type: "float")]
    #[Assert\Range(
        min: 0,
        max: 100,
        notInRangeMessage: "Le pourcentage doit être compris entre {{ min }} et {{ max }}."
    )]
    private ?float $percentage = null;

    #[ORM\Column(type: "boolean")]
    private ?bool $passed = null;

    #[ORM\Column(name: "completed_at", type: "datetime")]
    #[Assert\NotNull(message: "La date de complétion est obligatoire.")]
    #[Assert\LessThanOrEqual("today", message: "La date ne peut pas être dans le futur.")]
    private ?\DateTimeInterface $completedAt = null;

    // ──────────────────────────────────────────────────────────────
    // GETTERS & SETTERS
    // ──────────────────────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getTraining(): ?Training_program
    {
        return $this->training;
    }

    public function setTraining(?Training_program $training): self
    {
        $this->training = $training;
        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): self
    {
        $this->score = $score;
        return $this;
    }

    public function getTotalQuestions(): ?int
    {
        return $this->totalQuestions;
    }

    public function setTotalQuestions(?int $totalQuestions): self
    {
        $this->totalQuestions = $totalQuestions;
        return $this;
    }

    public function getPercentage(): ?float
    {
        return $this->percentage;
    }

    public function setPercentage(?float $percentage): self
    {
        $this->percentage = $percentage;
        return $this;
    }

    public function isPassed(): ?bool
    {
        return $this->passed;
    }

    public function setPassed(?bool $passed): self
    {
        $this->passed = $passed;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }
}