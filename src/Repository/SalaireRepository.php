<?php

namespace App\Repository;

use App\Entity\Salaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SalaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Salaire::class);
    }

    public function findByUsernameSearch(string $search): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.user', 'u')                    // Jointure avec UserAccount
            ->where('u.username LIKE :search')        // Filtre sur username
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();
    }
}