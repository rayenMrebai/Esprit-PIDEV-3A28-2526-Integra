<?php
// src/Repository/InscriptionRepository.php

namespace App\Repository;

use App\Entity\Inscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
    public function findExistingInscription($user, $formation): ?Inscription
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