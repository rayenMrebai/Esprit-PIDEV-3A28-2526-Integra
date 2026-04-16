<?php

namespace App\Repository;

use App\Entity\Salaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Salaire>
 */
class SalaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Salaire::class);
    }

    /**
     * ⭐ Recherche par nom d'employé uniquement
     */
    public function findByUsernameSearch(string $search): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.user', 'u')
            ->where('u.username LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * ⭐ Filtrage par statut uniquement
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.user', 'u')
            ->where('s.status = :status')
            ->setParameter('status', $status)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * ⭐ Recherche + Filtrage par statut combinés
     */
    public function findBySearchAndStatus(string $search, string $status): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.user', 'u')
            ->where('u.username LIKE :search')
            ->andWhere('s.status = :status')
            ->setParameter('search', '%' . $search . '%')
            ->setParameter('status', $status)
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();
    }
}