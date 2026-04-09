<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\SkillRepository;
use App\Entity\Training_program;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SkillRepository::class)]
class Skill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 100)]
    #[Assert\NotBlank(message: "Le nom de la compétence est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères."
    )]
    private string $nom;

    #[ORM\Column(type: "text", nullable: true)]
    #[Assert\Length(
        max: 500,
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $description = null;

    #[ORM\Column(type: "integer", nullable: true)]
    #[Assert\Range(
        min: 1,
        max: 5,
        notInRangeMessage: "Le niveau requis doit être compris entre {{ min }} et {{ max }}."
    )]
    private ?int $level_required = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    #[Assert\Choice(
        choices: ["technique", "soft", "management", "autre"],
        message: "La catégorie doit être: technique, soft, management ou autre."
    )]
    private ?string $categorie = null;

    #[ORM\ManyToOne(targetEntity: Training_program::class, inversedBy: "skills")]
    #[ORM\JoinColumn(name: "trainingprogram_id", referencedColumnName: "id", nullable: true)]
    private ?Training_program $trainingProgram = null;

    // Relation ManyToMany avec User
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: "skills")]
    private Collection $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

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

    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
        }
        return $this;
    }

    public function removeUser(User $user): self
    {
        $this->users->removeElement($user);
        return $this;
    }
}