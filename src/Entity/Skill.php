<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\SkillRepository;
use App\Entity\Training_program;

#[ORM\Entity(repositoryClass: SkillRepository::class)]
class Skill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 100)]
    private string $nom;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $level_required = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $categorie = null;

    #[ORM\ManyToOne(targetEntity: Training_program::class, inversedBy: "skills")]
    #[ORM\JoinColumn(name: 'trainingprogram_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Training_program $trainingProgram = null;

    // ──────────────────────────────────────────────────────────────
    // GETTERS & SETTERS
    // ──────────────────────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
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

public function getLevelRequired(): ?int
{
    return $this->level_required;
}

public function setLevelRequired(?int $level_required): self
{
    $this->level_required = $level_required;
    return $this;
}


    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): self
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getTrainingProgram(): ?Training_program
    {
        return $this->trainingProgram;
    }

    public function setTrainingProgram(?Training_program $trainingProgram): self
    {
        $this->trainingProgram = $trainingProgram;
        return $this;
    }
}