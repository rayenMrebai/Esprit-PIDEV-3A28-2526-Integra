<?php
// src/Repository/InscriptionRepository.php

namespace App\Repository;

use App\Entity\Inscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\UserAccount;
use App\Entity\Training_program;

/**
 * @extends ServiceEntityRepository<Inscription>
 */
class InscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inscription::class);
    }

    /**
     * Trouve les inscriptions d'un utilisateur
     */
/**
 * @return Inscription[]
 */
    public function findByUser($user): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.user = :user')
            ->setParameter('user', $user)
            ->orderBy('i.dateDemande', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les inscriptions par statut
     */
    /**
 * @return array<int, Inscription>
 */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.status = :status')
            ->setParameter('status', $status)
            ->orderBy('i.dateDemande', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un utilisateur est déjà inscrit à une formation
     */
    /**
 * @return array<int, Inscription>
 */
public function findExistingInscription(Training_program $formation,UserAccount $user): ?Inscription
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.user = :user')
            ->andWhere('i.formation = :formation')
            ->setParameter('user', $user)
            ->setParameter('formation', $formation)
            ->getQuery()
            ->getOneOrNullResult();
    }
}