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
     * Returns all users sorted by riskScore descending.
     */
    public function findAllWithRisk(): array
    {
        $now   = new \DateTime();
        $users = $this->findAll();
        $results = [];

        foreach ($users as $user) {
            $lastLogin    = $user->getLastLogin();
            $createdAt    = $user->getAccountCreatedDate();
            $role         = strtoupper((string) $user->getRole());

            // ── 1. Days-since-login score (0–50) ─────────────────────────────
            if (!$lastLogin) {
                $daysSince      = null;
                $loginScore     = 50;   // never logged in → max penalty
                $trend          = 'never';
            } else {
                $daysSince      = (int) $now->diff($lastLogin)->days;
                $loginScore     = match(true) {
                    $daysSince > 60 => 50,
                    $daysSince > 30 => 40,
                    $daysSince > 14 => 28,
                    $daysSince > 7  => 18,
                    $daysSince > 3  => 10,
                    default         => 0,
                };
                $trend = match(true) {
                    $daysSince > 30 => 'declining',
                    $daysSince > 7  => 'at_risk',
                    default         => 'healthy',
                };
            }

            // ── 2. Account age factor (0–20) ─────────────────────────────────
            // Young accounts (< 7 days) get 0 — they haven't had time to be active.
            // Old dormant accounts score higher.
            $ageScore = 0;
            if ($createdAt) {
                $accountAgeDays = (int) $now->diff($createdAt)->days;
                $ageScore = match(true) {
                    $accountAgeDays > 180 => 20,
                    $accountAgeDays > 90  => 14,
                    $accountAgeDays > 30  => 8,
                    $accountAgeDays > 7   => 4,
                    default               => 0,   // brand-new account
                };
            }

            // ── 3. Role weight (0–20) ────────────────────────────────────────
            // Inactive admins/managers are a bigger risk than inactive employees.
            $roleScore = match($role) {
                'ADMINISTRATEUR' => 20,
                'MANAGER'        => 12,
                default          => 5,   // EMPLOYE
            };

            // ── 4. Trend bonus (0–10) ────────────────────────────────────────
            $trendScore = match($trend) {
                'never'    => 10,
                'declining'=> 6,
                'at_risk'  => 3,
                default    => 0,
            };

            // ── Final score ──────────────────────────────────────────────────
            $riskScore = min(100, $loginScore + $ageScore + $roleScore + $trendScore);

            $riskLevel = match(true) {
                $riskScore >= 80 => 'Critical',
                $riskScore >= 55 => 'High',
                $riskScore >= 30 => 'Medium',
                $riskScore >= 10 => 'Low',
                default          => 'Active',
            };

            $results[] = [
                    'user'               => $user,
                    'riskScore'          => $riskScore,
                    'riskLevel'          => $riskLevel,
                    'trend'              => $trend,
                    'daysSinceLastLogin' => $daysSince ?? 'Jamais',
                    'breakdown'          => [
                            'login' => $loginScore,
                            'age'   => $ageScore,
                            'role'  => $roleScore,
                            'trend' => $trendScore,
                    ],
            ];
        }

        usort($results, fn($a, $b) => $b['riskScore'] <=> $a['riskScore']);
        return $results;
    }
}
