<?php
// src/Entity/Quiz_result.php

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

    // ✅ Relation uniquement — plus de colonne scalaire user_id
    #[ORM\ManyToOne(targetEntity: UserAccount::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "userid", nullable: false)]
    private ?UserAccount $user = null;

    #[ORM\ManyToOne(targetEntity: Training_program::class)]
    #[ORM\JoinColumn(name: "training_id", referencedColumnName: "id", nullable: false)]
    private ?Training_program $training = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $score = null;

    #[ORM\Column(name: "total_questions", type: "integer", nullable: true)]
    private ?int $totalQuestions = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $percentage = null;

    #[ORM\Column(type: "boolean", nullable: true)]
    private ?bool $passed = null;

    #[ORM\Column(name: "completed_at", type: "datetime", nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

    #[ORM\Column(type: "json", nullable: true)]
    private ?array $questions = null;

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?UserAccount { return $this->user; }
    public function setUser(?UserAccount $user): self { $this->user = $user; return $this; }

    // ✅ Méthode utilitaire conservée pour compatibilité avec les templates existants
    public function getUserId(): ?int { return $this->user?->getUserId(); }

    public function getTraining(): ?Training_program { return $this->training; }
    public function setTraining(?Training_program $training): self { $this->training = $training; return $this; }

    public function getScore(): ?int { return $this->score; }
    public function setScore(?int $score): self { $this->score = $score; return $this; }

    public function getTotalQuestions(): ?int { return $this->totalQuestions; }
    public function setTotalQuestions(?int $totalQuestions): self { $this->totalQuestions = $totalQuestions; return $this; }

    public function getPercentage(): ?float { return $this->percentage; }
    public function setPercentage(?float $percentage): self { $this->percentage = $percentage; return $this; }

    public function isPassed(): ?bool { return $this->passed; }
    public function setPassed(?bool $passed): self { $this->passed = $passed; return $this; }

    public function getCompletedAt(): ?\DateTimeInterface { return $this->completedAt; }
    public function setCompletedAt(?\DateTimeInterface $completedAt): self { $this->completedAt = $completedAt; return $this; }

    public function getQuestions(): ?array { return $this->questions; }
    public function setQuestions(?array $questions): self { $this->questions = $questions; return $this; }
}