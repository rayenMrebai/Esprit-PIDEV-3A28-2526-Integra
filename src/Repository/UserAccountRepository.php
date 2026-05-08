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
            ->setParameter('q', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }

    public function findByRoleAndSearch(?string $role, ?string $search): array
    {
        $qb = $this->createQueryBuilder('u');
        if ($role !== null && $role !== '') {
            $qb->andWhere('u.role = :role')->setParameter('role', $role);
        }
        if ($search !== null && $search !== '') {
            $qb->andWhere('u.email LIKE :search OR u.username LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        return $qb->getQuery()->getResult();
    }

    public function countActiveVsInactive(): array
    {
        $active = (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.userId)')
            ->where('u.isActive = true')
            ->getQuery()
            ->getSingleScalarResult();

        $inactive = (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.userId)')
            ->where('u.isActive = false')
            ->getQuery()
            ->getSingleScalarResult();

        return ['active' => $active, 'inactive' => $inactive];
    }

    /**
     * Deactivate users who have not logged in for more than 3 days.
     */
    public function deactivateInactiveUsers(): int
    {
        $threshold = new \DateTime('-3 days');
        return $this->createQueryBuilder('u')
            ->update()
            ->set('u.isActive', ':false')
            ->where('u.lastLogin < :threshold')
            ->andWhere('u.isActive = :true')
            ->setParameter('false', false)
            ->setParameter('true', true)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->execute();
    }

    /**
     * Multi-factor inactivity risk scoring.
     *
     * Score is 0–100, composed of:
     *  - 50 pts  days since last login  (main signal)
     *  - 20 pts  account age factor     (new accounts get lower risk)
     *  - 20 pts  role weight            (admins matter more)
     *  - 10 pts  trend bonus            (never logged in = worst case)
     *
     * @return array<int, array{
     *     user: UserAccount,
     *     riskScore: int,
     *     riskLevel: string,
     *     trend: string,
     *     daysSinceLastLogin: int|string,
     *     breakdown: array{login: int, age: int, role: int, trend: int}
     * }>
     */
    public function findAllWithRisk(): array
    {
        $now = new \DateTime();
        $users = $this->findAll();
        $results = [];

        foreach ($users as $user) {
            $lastLogin = $user->getLastLogin();
            $createdAt = $user->getAccountCreatedDate();
            $role = strtoupper($user->getRole());

            // 1. Days‑since‑login score (0–50)
            if ($lastLogin === null) {
                $loginScore = 50;
                $trend = 'never';
                $daysSince = 'Jamais';
            } else {
                $daysSinceInt = (int) $now->diff($lastLogin)->days;
                $daysSince = $daysSinceInt;
                $loginScore = match (true) {
                    $daysSinceInt > 60 => 50,
                    $daysSinceInt > 30 => 40,
                    $daysSinceInt > 14 => 28,
                    $daysSinceInt > 7 => 18,
                    $daysSinceInt > 3 => 10,
                    default => 0,
                };
                $trend = match (true) {
                    $daysSinceInt > 30 => 'declining',
                    $daysSinceInt > 7 => 'at_risk',
                    default => 'healthy',
                };
            }

            // 2. Account age factor (0–20)
            $ageScore = 0;
            if ($createdAt !== null) {
                $accountAgeDays = (int) $now->diff($createdAt)->days;
                $ageScore = match (true) {
                    $accountAgeDays > 180 => 20,
                    $accountAgeDays > 90 => 14,
                    $accountAgeDays > 30 => 8,
                    $accountAgeDays > 7 => 4,
                    default => 0,
                };
            }

            // 3. Role weight (0–20)
            $roleScore = match ($role) {
                'ADMINISTRATEUR' => 20,
                'MANAGER' => 12,
                default => 5,
            };

            // 4. Trend bonus (0–10)
            $trendScore = match ($trend) {
                'never' => 10,
                'declining' => 6,
                'at_risk' => 3,
                default => 0,
            };

            $riskScore = min(100, $loginScore + $ageScore + $roleScore + $trendScore);

            $riskLevel = match (true) {
                $riskScore >= 80 => 'Critical',
                $riskScore >= 55 => 'High',
                $riskScore >= 30 => 'Medium',
                $riskScore >= 10 => 'Low',
                default => 'Active',
            };

            $results[] = [
                'user' => $user,
                'riskScore' => $riskScore,
                'riskLevel' => $riskLevel,
                'trend' => $trend,
                'daysSinceLastLogin' => $daysSince,
                'breakdown' => [
                    'login' => $loginScore,
                    'age' => $ageScore,
                    'role' => $roleScore,
                    'trend' => $trendScore,
                ],
            ];
        }

        usort($results, fn($a, $b) => $b['riskScore'] <=> $a['riskScore']);
        return $results;
    }
}