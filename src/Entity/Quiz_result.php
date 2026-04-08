<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\Quiz_resultRepository;

#[ORM\Entity(repositoryClass: Quiz_resultRepository::class)]
class Quiz_result
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "user_id", type: "integer")]
    private ?int $userId = null;

    #[ORM\ManyToOne(targetEntity: Training_program::class)]
    #[ORM\JoinColumn(name: "training_id", referencedColumnName: "id")]
    private ?Training_program $training = null;

    #[ORM\Column(type: "integer")]
    private ?int $score = null;

    #[ORM\Column(name: "total_questions", type: "integer")]
    private ?int $totalQuestions = null;

    #[ORM\Column(type: "float")]
    private ?float $percentage = null;

    #[ORM\Column(type: "boolean")]
    private ?bool $passed = null;

    #[ORM\Column(name: "completed_at", type: "datetime")]
    private ?\DateTimeInterface $completedAt = null;

    // Getters et setters

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