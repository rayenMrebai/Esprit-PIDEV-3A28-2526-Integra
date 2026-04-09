<?php

namespace App\Repository;

use App\Entity\Projectassignment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Projectassignment>
 */
class ProjectassignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Projectassignment::class);
    }

    /**
     * Recherche les affectations avec filtres
     *
     * @param string|null $searchTerm   Recherche textuelle (nom projet, employé, rôle)
     * @param int|null    $projectId    ID du projet spécifique
     * @param string|null $role         Rôle exact
     * @return Projectassignment[]
     */
    public function findByFilters(?string $searchTerm = null, ?int $projectId = null, ?string $role = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.project', 'p')
            ->leftJoin('a.userAccount', 'u');

        // Recherche textuelle (nom du projet, nom d'utilisateur, rôle)
        if ($searchTerm) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('p.name', ':search'),
                    $qb->expr()->like('u.username', ':search'),
                    $qb->expr()->like('a.role', ':search')
                )
            )->setParameter('search', '%' . $searchTerm . '%');
        }

        // Filtre par projet (correction : p.projectId au lieu de p.id)
        if ($projectId) {
            $qb->andWhere('p.projectId = :projectId')
                ->setParameter('projectId', $projectId);
        }

        // Filtre par rôle exact
        if ($role) {
            $qb->andWhere('a.role = :role')
                ->setParameter('role', $role);
        }

        // ✅ Tri par ID croissant (1, 2, 3...)
        $qb->orderBy('a.idAssignment', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère la liste des rôles distincts présents dans les affectations
     * @return string[]
     */
    public function findDistinctRoles(): array
    {
        $result = $this->createQueryBuilder('a')
            ->select('DISTINCT a.role')
            ->orderBy('a.role', 'ASC')
            ->getQuery()
            ->getResult();

        return array_column($result, 'role');
    }
}