<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\JobpositionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: JobpositionRepository::class)]
#[ORM\Table(name: 'jobposition')]
class Jobposition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idJob', type: 'integer')]
    private int $idJob = 0;

    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    #[Assert\Length(min: 3, max: 100)]
    private ?string $title = null;

    #[ORM\Column(name: 'departement', type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Le département est obligatoire.")]
    private ?string $departement = null;

    #[ORM\Column(name: 'employee_type', type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Le type d'emploi est obligatoire.")]
    private ?string $employeeType = null;

    #[ORM\Column(name: 'description', type: 'string', length: 255)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(min: 10)]
    private ?string $description = null;

    #[ORM\Column(name: 'status', type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Le statut est obligatoire.")]
    #[Assert\Choice(choices: ['Open', 'Closed'])]
    private ?string $status = null;

    #[ORM\Column(name: 'posted_at', type: 'date')]
    #[Assert\NotNull(message: "La date de publication est obligatoire.")]
    private ?\DateTimeInterface $postedAt = null;

    /** @var Collection<int, Candidat> */
    #[ORM\OneToMany(mappedBy: 'jobposition', targetEntity: Candidat::class)]
    private Collection $candidats;

    public function __construct()
    {
        $this->candidats = new ArrayCollection();
    }

    public function getIdJob(): int { return $this->idJob; }
    public function getTitle(): ?string { return $this->title; }
    public function getDepartement(): ?string { return $this->departement; }
    public function getEmployeeType(): ?string { return $this->employeeType; }
    public function getDescription(): ?string { return $this->description; }
    public function getStatus(): ?string { return $this->status; }
    public function getPostedAt(): ?\DateTimeInterface { return $this->postedAt; }
    /** @return Collection<int, Candidat> */
    public function getCandidats(): Collection { return $this->candidats; }

    public function setTitle(?string $title): self { $this->title = $title; return $this; }
    public function setDepartement(?string $departement): self { $this->departement = $departement; return $this; }
    public function setEmployeeType(?string $employeeType): self { $this->employeeType = $employeeType; return $this; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }
    public function setPostedAt(?\DateTimeInterface $postedAt): self { $this->postedAt = $postedAt; return $this; }

    public function addCandidat(Candidat $candidat): self
    {
        if (!$this->candidats->contains($candidat)) {
            $this->candidats->add($candidat);
            $candidat->setJobposition($this);
        }
        return $this;
    }

    public function removeCandidat(Candidat $candidat): self
    {
        if ($this->candidats->removeElement($candidat)) {
            if ($candidat->getJobposition() === $this) {
                $candidat->setJobposition(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->title ?? '';
    }
}