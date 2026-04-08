<?php

namespace App\Entity;

use App\Repository\JobpositionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobpositionRepository::class)]
#[ORM\Table(name: 'jobposition')]
class Jobposition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idJob', type: 'integer')]
    private ?int $idJob = null;

    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    private ?string $title = null;

    #[ORM\Column(name: 'departement', type: 'string', length: 255)]
    private ?string $departement = null;

    #[ORM\Column(name: 'employeeType', type: 'string', length: 255)]
    private ?string $employeeType = null;

    #[ORM\Column(name: 'description', type: 'string', length: 255)]
    private ?string $description = null;

    #[ORM\Column(name: 'status', type: 'string', length: 255)]
    private ?string $status = null;

    #[ORM\Column(name: 'postedAt', type: 'date')]
    private ?\DateTimeInterface $postedAt = null;

    #[ORM\OneToMany(mappedBy: 'jobposition', targetEntity: Candidat::class)]
    private Collection $candidats;

    public function __construct()
    {
        $this->candidats = new ArrayCollection();
    }

    public function getIdJob(): ?int
    {
        return $this->idJob;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDepartement(): ?string
    {
        return $this->departement;
    }

    public function setDepartement(string $departement): self
    {
        $this->departement = $departement;
        return $this;
    }

    public function getEmployeeType(): ?string
    {
        return $this->employeeType;
    }

    public function setEmployeeType(string $employeeType): self
    {
        $this->employeeType = $employeeType;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
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

    public function getPostedAt(): ?\DateTimeInterface
    {
        return $this->postedAt;
    }

    public function setPostedAt(\DateTimeInterface $postedAt): self
    {
        $this->postedAt = $postedAt;
        return $this;
    }

    /**
     * @return Collection<int, Candidat>
     */
    public function getCandidats(): Collection
    {
        return $this->candidats;
    }

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