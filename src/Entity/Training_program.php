<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\Training_programRepository;

use Doctrine\Common\Collections\Collection;
use App\Entity\Training_program_skill;

#[ORM\Entity(repositoryClass: Training_programRepository::class)]
class Training_program
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 150)]
    private string $title;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "integer")]
    private int $duration;

    #[ORM\Column(type: "string", length: 50)]
    private string $type;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $start_date;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $end_date;

    #[ORM\Column(type: "string", length: 20)]
    private string $status;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($value)
    {
        $this->title = $value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function setDuration($value)
    {
        $this->duration = $value;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($value)
    {
        $this->type = $value;
    }

    public function getStart_date()
    {
        return $this->start_date;
    }

    public function setStart_date($value)
    {
        $this->start_date = $value;
    }

    public function getEnd_date()
    {
        return $this->end_date;
    }

    public function setEnd_date($value)
    {
        $this->end_date = $value;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->status = $value;
    }

    #[ORM\OneToMany(mappedBy: "training_id", targetEntity: Quiz_result::class)]
    private Collection $quiz_results;

        public function getQuiz_results(): Collection
        {
            return $this->quiz_results;
        }
    
        public function addQuiz_result(Quiz_result $quiz_result): self
        {
            if (!$this->quiz_results->contains($quiz_result)) {
                $this->quiz_results[] = $quiz_result;
                $quiz_result->setTraining_id($this);
            }
    
            return $this;
        }
    
        public function removeQuiz_result(Quiz_result $quiz_result): self
        {
            if ($this->quiz_results->removeElement($quiz_result)) {
                // set the owning side to null (unless already changed)
                if ($quiz_result->getTraining_id() === $this) {
                    $quiz_result->setTraining_id(null);
                }
            }
    
            return $this;
        }

    #[ORM\OneToMany(mappedBy: "trainingprogram_id", targetEntity: Skill::class)]
    private Collection $skills;

        public function getSkills(): Collection
        {
            return $this->skills;
        }
    
        public function addSkill(Skill $skill): self
        {
            if (!$this->skills->contains($skill)) {
                $this->skills[] = $skill;
                $skill->setTrainingprogram_id($this);
            }
    
            return $this;
        }
    
        public function removeSkill(Skill $skill): self
        {
            if ($this->skills->removeElement($skill)) {
                // set the owning side to null (unless already changed)
                if ($skill->getTrainingprogram_id() === $this) {
                    $skill->setTrainingprogram_id(null);
                }
            }
    
            return $this;
        }

    #[ORM\OneToMany(mappedBy: "training_program_id", targetEntity: Training_program_skill::class)]
    private Collection $training_program_skills;

        public function getTraining_program_skills(): Collection
        {
            return $this->training_program_skills;
        }
    
        public function addTraining_program_skill(Training_program_skill $training_program_skill): self
        {
            if (!$this->training_program_skills->contains($training_program_skill)) {
                $this->training_program_skills[] = $training_program_skill;
                $training_program_skill->setTraining_program_id($this);
            }
    
            return $this;
        }
    
        public function removeTraining_program_skill(Training_program_skill $training_program_skill): self
        {
            if ($this->training_program_skills->removeElement($training_program_skill)) {
                // set the owning side to null (unless already changed)
                if ($training_program_skill->getTraining_program_id() === $this) {
                    $training_program_skill->setTraining_program_id(null);
                }
            }
    
            return $this;
        }
}
