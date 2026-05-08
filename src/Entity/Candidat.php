<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CandidatRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CandidatRepository::class)]
#[ORM\Table(name: 'candidat')]
class Candidat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id = 0;

    #[ORM\Column(name: 'first_name', type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Le prénom est obligatoire.")]
    #[Assert\Length(min: 2, max: 50)]
    private ?string $firstName = null;

    #[ORM\Column(name: 'last_name', type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    #[Assert\Length(min: 2, max: 50)]
    private ?string $lastName = null;

    #[ORM\Column(name: 'email', type: 'string', length: 255)]
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas valide.")]
    private ?string $email = null;

    #[ORM\Column(name: 'phone', type: 'integer')]
    #[Assert\NotBlank(message: "Le téléphone est obligatoire.")]
    #[Assert\Type(type: 'integer', message: "Le téléphone doit être un nombre.")]
    private ?int $phone = null;

    #[ORM\Column(name: 'education_level', type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Le niveau d'études est obligatoire.")]
    private ?string $educationLevel = null;

    #[ORM\Column(name: 'skills', type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Les compétences sont obligatoires.")]
    private ?string $skills = null;

    #[ORM\Column(name: 'status', type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Le statut est obligatoire.")]
    private ?string $status = null;

    #[ORM\ManyToOne(targetEntity: Jobposition::class, inversedBy: 'candidats')]
    #[ORM\JoinColumn(name: 'jobposition_id', referencedColumnName: 'idJob', nullable: true)]
    private ?Jobposition $jobposition = null;

    // Getters & setters
    public function getId(): int { return $this->id; }
    public function getFirstName(): ?string { return $this->firstName; }
    public function getLastName(): ?string { return $this->lastName; }
    public function getEmail(): ?string { return $this->email; }
    public function getPhone(): ?int { return $this->phone; }
    public function getEducationLevel(): ?string { return $this->educationLevel; }
    public function getSkills(): ?string { return $this->skills; }
    public function getStatus(): ?string { return $this->status; }
    public function getJobposition(): ?Jobposition { return $this->jobposition; }

    public function setFirstName(?string $firstName): self { $this->firstName = $firstName; return $this; }
    public function setLastName(?string $lastName): self { $this->lastName = $lastName; return $this; }
    public function setEmail(?string $email): self { $this->email = $email; return $this; }
    public function setPhone(?int $phone): self { $this->phone = $phone; return $this; }
    public function setEducationLevel(?string $educationLevel): self { $this->educationLevel = $educationLevel; return $this; }
    public function setSkills(?string $skills): self { $this->skills = $skills; return $this; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }
    public function setJobposition(?Jobposition $jobposition): self { $this->jobposition = $jobposition; return $this; }
}