<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\SkillRepository;

use App\Entity\Training_program;
use Doctrine\Common\Collections\Collection;
use App\Entity\Training_program_skill;

#[ORM\Entity(repositoryClass: SkillRepository::class)]
class Skill
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 100)]
    private string $nom;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "integer")]
    private int $level_required;

    #[ORM\Column(type: "string", length: 50)]
    private string $categorie;

        #[ORM\ManyToOne(targetEntity: Training_program::class, inversedBy: "skills")]
    #[ORM\JoinColumn(name: 'trainingprogram_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Training_program $trainingprogram_id;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function setNom($value)
    {
        $this->nom = $value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getLevel_required()
    {
        return $this->level_required;
    }

    public function setLevel_required($value)
    {
        $this->level_required = $value;
    }

    public function getCategorie()
    {
        return $this->categorie;
    }

    public function setCategorie($value)
    {
        $this->categorie = $value;
    }

    public function getTrainingprogram_id()
    {
        return $this->trainingprogram_id;
    }

    public function setTrainingprogram_id($value)
    {
        $this->trainingprogram_id = $value;
    }

    #[ORM\OneToMany(mappedBy: "skill_id", targetEntity: Training_program_skill::class)]
    private Collection $training_program_skills;

        public function getTraining_program_skills(): Collection
        {
            return $this->training_program_skills;
        }
    
        public function addTraining_program_skill(Training_program_skill $training_program_skill): self
        {
            if (!$this->training_program_skills->contains($training_program_skill)) {
                $this->training_program_skills[] = $training_program_skill;
                $training_program_skill->setSkill_id($this);
            }
    
            return $this;
        }
    
        public function removeTraining_program_skill(Training_program_skill $training_program_skill): self
        {
            if ($this->training_program_skills->removeElement($training_program_skill)) {
                // set the owning side to null (unless already changed)
                if ($training_program_skill->getSkill_id() === $this) {
                    $training_program_skill->setSkill_id(null);
                }
            }
    
            return $this;
        }
}
