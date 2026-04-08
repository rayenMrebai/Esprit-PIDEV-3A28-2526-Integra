<?php

namespace App\Entity;

use App\Repository\CandidatRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CandidatRepository::class)]
#[ORM\Table(name: 'candidat')]
class Candidat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'firstName', type: 'string', length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(name: 'lastName', type: 'string', length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(name: 'email', type: 'string', length: 255)]
    private ?string $email = null;

    #[ORM\Column(name: 'phone', type: 'integer')]
    private ?int $phone = null;

    #[ORM\Column(name: 'educationLevel', type: 'string', length: 255)]
    private ?string $educationLevel = null;

    #[ORM\Column(name: 'skills', type: 'string', length: 255)]
    private ?string $skills = null;

    #[ORM\Column(name: 'status', type: 'string', length: 255)]
    private ?string $status = null;

    #[ORM\ManyToOne(targetEntity: Jobposition::class, inversedBy: 'candidats')]
    #[ORM\JoinColumn(name: 'idJob', referencedColumnName: 'idJob', nullable: true)]
    private ?Jobposition $jobposition = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?int
    {
        return $this->phone;
    }

    public function setPhone(int $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getEducationLevel(): ?string
    {
        return $this->educationLevel;
    }

    public function setEducationLevel(string $educationLevel): self
    {
        $this->educationLevel = $educationLevel;
        return $this;
    }

    public function getSkills(): ?string
    {
        return $this->skills;
    }

    public function setSkills(string $skills): self
    {
        $this->skills = $skills;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getJobposition(): ?Jobposition
    {
        return $this->jobposition;
    }

    public function setJobposition(?Jobposition $jobposition): self
    {
        $this->jobposition = $jobposition;
        return $this;
    }
}