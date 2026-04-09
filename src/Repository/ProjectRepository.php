<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    // Recherche multi‑critères (nom, ID, statut)
    public function searchProjects(?string $search, ?string $status, ?\DateTimeInterface $startDateFrom, ?\DateTimeInterface $startDateTo): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($search) {
            $qb->andWhere('p.name LIKE :search OR CAST(p.projectId AS string) LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }
        if ($status && $status !== 'all') {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $status);
        }
        if ($startDateFrom) {
            $qb->andWhere('p.startDate >= :startFrom')
                ->setParameter('startFrom', $startDateFrom);
        }
        if ($startDateTo) {
            $qb->andWhere('p.startDate <= :startTo')
                ->setParameter('startTo', $startDateTo);
        }

        return $qb->getQuery()->getResult();
    }

    // Nombre d'employés par projet (à utiliser dans le contrôleur)
    public function countEmployeesForProject(int $projectId): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT COUNT(*) FROM projectassignment WHERE projectId = :projectId';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('projectId', $projectId);
        $result = $stmt->executeQuery();
        return (int) $result->fetchOne();
    }
}