<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Project;
use App\Entity\UserAccount;
use App\Entity\Projectassignment;
use App\Repository\ProjectassignmentRepository;

class EmployeeMatchingService
{
    public function __construct(
        private HuggingFaceService $hfService,
        private ProjectassignmentRepository $assignmentRepo
    ) {}

    /**
     * @param UserAccount[] $employees
     * @return array<int, array<string, mixed>>
     */
    public function rankEmployeesForProject(Project $project, array $employees): array
    {
        $projectProfile = $this->buildProjectProfile($project);
        $projectVector = $this->hfService->getEmbedding($projectProfile);

        $results = [];

        foreach ($employees as $employee) {
            $history = $this->assignmentRepo->findBy(['userAccount' => $employee]);
            $currentAllocation = $this->getCurrentAllocation($employee);

            if ($currentAllocation > 90.0) {
                continue;
            }

            $employeeProfile = $this->buildEmployeeProfile($employee, $history);
            $employeeVector = $this->hfService->getEmbedding($employeeProfile);

            $similarity = $this->hfService->cosineSimilarity($projectVector, $employeeVector);
            $dominantRole = $this->getDominantRole($history);

            $finalScore = $similarity * (1.0 - $currentAllocation / 100.0);

            $results[] = [
                'userId'            => $employee->getUserId(),
                'username'          => $employee->getUsername(),
                'dominantRole'      => $dominantRole,
                'similarity'        => $similarity,
                'currentAllocation' => $currentAllocation,
                'finalScore'        => $finalScore,
            ];
        }

        usort($results, fn($a, $b) => $b['finalScore'] <=> $a['finalScore']);
        return $results;
    }

    private function getCurrentAllocation(UserAccount $employee): float
    {
        $assignments = $this->assignmentRepo->findBy(['userAccount' => $employee]);
        if (count($assignments) === 0) {
            return 0.0;
        }
        $sum = array_sum(array_map(fn($a) => $a->getAllocationRate(), $assignments));
        return $sum / count($assignments);
    }

    private function buildProjectProfile(Project $project): string
    {
        $parts = [
            "Project: " . $project->getName(),
            $project->getDescription() ? "Description: " . $project->getDescription() : "",
            "Status: " . $project->getStatus(),
        ];
        return implode('. ', array_filter($parts));
    }

    /**
     * @param Projectassignment[] $history
     */
    private function buildEmployeeProfile(UserAccount $employee, array $history): string
    {
        $parts = ["Employee: " . $employee->getUsername()];

        if (!empty($history)) {
            $roles = array_unique(array_map(fn($a) => $a->getRole(), $history));
            $parts[] = "Roles: " . implode(', ', $roles);

            $avgAlloc = array_sum(array_map(fn($a) => $a->getAllocationRate(), $history)) / count($history);
            $parts[] = "Average allocation: " . round($avgAlloc) . "%";
            $parts[] = "Number of projects: " . count($history);
        }

        return implode('. ', $parts);
    }

    /**
     * @param Projectassignment[] $history
     */
    private function getDominantRole(array $history): string
    {
        if (empty($history)) {
            return 'Not assigned';
        }

        $roleCounts = [];
        foreach ($history as $assignment) {
            $role = $assignment->getRole();
            if (!isset($roleCounts[$role])) {
                $roleCounts[$role] = 0;
            }
            $roleCounts[$role]++;
        }

        arsort($roleCounts);
        $key = array_key_first($roleCounts);
        return $key ?? 'Not assigned';
    }
}