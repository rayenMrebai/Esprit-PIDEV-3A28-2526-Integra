<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\Quiz_resultRepository;

use App\Entity\Training_program;

#[ORM\Entity(repositoryClass: Quiz_resultRepository::class)]
class Quiz_result
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "integer")]
    private int $user_id;

        #[ORM\ManyToOne(targetEntity: Training_program::class, inversedBy: "quiz_results")]
    #[ORM\JoinColumn(name: 'training_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Training_program $training_id;

    #[ORM\Column(type: "integer")]
    private int $score;

    #[ORM\Column(type: "integer")]
    private int $total_questions;

    #[ORM\Column(type: "float")]
    private float $percentage;

    #[ORM\Column(type: "boolean")]
    private bool $passed;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $completed_at;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getUser_id()
    {
        return $this->user_id;
    }

    public function setUser_id($value)
    {
        $this->user_id = $value;
    }

    public function getTraining_id()
    {
        return $this->training_id;
    }

    public function setTraining_id($value)
    {
        $this->training_id = $value;
    }

    public function getScore()
    {
        return $this->score;
    }

    public function setScore($value)
    {
        $this->score = $value;
    }

    public function getTotal_questions()
    {
        return $this->total_questions;
    }

    public function setTotal_questions($value)
    {
        $this->total_questions = $value;
    }

    public function getPercentage()
    {
        return $this->percentage;
    }

    public function setPercentage($value)
    {
        $this->percentage = $value;
    }

    public function getPassed()
    {
        return $this->passed;
    }

    public function setPassed($value)
    {
        $this->passed = $value;
    }

    public function getCompleted_at()
    {
        return $this->completed_at;
    }

    public function setCompleted_at($value)
    {
        $this->completed_at = $value;
    }
}
