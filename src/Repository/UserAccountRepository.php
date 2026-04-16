<?php

namespace App\Repository;

use App\Entity\UserAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserAccount>
 */
class UserAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAccount::class);
    }

    public function searchByEmailOrUsername(string $query): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.email LIKE :q OR u.username LIKE :q')
            ->setParameter('q', '%'.$query.'%')
            ->getQuery()
            ->getResult();
    }

    public function findByRoleAndSearch(?string $role, ?string $search): array
    {
        $qb = $this->createQueryBuilder('u');
        if ($role) {
            $qb->andWhere('u.role = :role')->setParameter('role', $role);
        }
        if ($search) {
            $qb->andWhere('u.email LIKE :search OR u.username LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }
        return $qb->getQuery()->getResult();
    }

    public function countActiveVsInactive(): array
    {
        $active = $this->createQueryBuilder('u')
            ->select('COUNT(u.userId)')
            ->where('u.isActive = true')
            ->getQuery()
            ->getSingleScalarResult();
        $inactive = $this->createQueryBuilder('u')
            ->select('COUNT(u.userId)')
            ->where('u.isActive = false')
            ->getQuery()
            ->getSingleScalarResult();
        return ['active' => (int)$active, 'inactive' => (int)$inactive];
    }
}