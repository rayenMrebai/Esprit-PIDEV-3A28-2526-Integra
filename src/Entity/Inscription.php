<?php

declare(strict_types=1);

// src/Entity/Inscription.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\InscriptionRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InscriptionRepository::class)]
#[ORM\Table(name: "inscription")]
class Inscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserAccount::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "userid", nullable: false)]
    private ?UserAccount $user = null;

    #[ORM\ManyToOne(targetEntity: Training_program::class)]
    #[ORM\JoinColumn(name: "formation_id", referencedColumnName: "id", nullable: false)]
    private ?Training_program $formation = null;

    #[ORM\Column(type: "string", length: 20)]
    private string $status = 'EN_ATTENTE';

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $motivation = null;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $dateDemande = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $dateReponse = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $commentaireAdmin = null;

    public function __construct()
    {
        $this->dateDemande = new \DateTime();
        $this->status = 'EN_ATTENTE';
    }

    // Getters et Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?UserAccount
    {
        return $this->user;
    }

    public function setUser(?UserAccount $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getFormation(): ?Training_program
    {
        return $this->formation;
    }

    public function setFormation(?Training_program $formation): self
    {
        $this->formation = $formation;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getMotivation(): ?string
    {
        return $this->motivation;
    }

    public function setMotivation(?string $motivation): self
    {
        $this->motivation = $motivation;
        return $this;
    }

    public function getDateDemande(): ?\DateTimeInterface
    {
        return $this->dateDemande;
    }

    public function setDateDemande(?\DateTimeInterface $dateDemande): self
    {
        $this->dateDemande = $dateDemande;
        return $this;
    }

    public function getDateReponse(): ?\DateTimeInterface
    {
        return $this->dateReponse;
    }

    public function setDateReponse(?\DateTimeInterface $dateReponse): self
    {
        $this->dateReponse = $dateReponse;
        return $this;
    }

    public function getCommentaireAdmin(): ?string
    {
        return $this->commentaireAdmin;
    }

    public function setCommentaireAdmin(?string $commentaireAdmin): self
    {
        $this->commentaireAdmin = $commentaireAdmin;
        return $this;
    }

    // Méthode utilitaire pour récupérer l'userId directement
    public function getUserId(): ?int
    {
        return $this->user ? $this->user->getUserId() : null;
    }
}