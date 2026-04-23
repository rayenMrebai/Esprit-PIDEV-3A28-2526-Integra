<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Projectassignment;
use App\Repository\UserAccountRepository;
use App\Service\EmployeeMatchingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ai')]
class AIRecommendationController extends AbstractController
{
    #[Route('/recommend/{projectId}', name: 'ai_recommend')]
    public function recommend(
        int $projectId,
        Project $project,
        UserAccountRepository $userRepo,
        EmployeeMatchingService $matchingService
    ): Response {
        $employees = $userRepo->findAll();
        $results = $matchingService->rankEmployeesForProject($project, $employees);

        return $this->render('ai/recommendation.html.twig', [
            'project' => $project,
            'results'  => $results,
        ]);
    }

    #[Route('/recommend/{projectId}/assign/{userId}', name: 'ai_assign', methods: ['POST'])]
    public function assign(
        int $projectId,
        int $userId,
        Project $project,
        UserAccountRepository $userRepo,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $employee = $userRepo->find($userId);
        if (!$employee) {
            throw $this->createNotFoundException('Employé non trouvé');
        }

        $assignment = new Projectassignment();
        $assignment->setProject($project);
        $assignment->setUserAccount($employee);
        $assignment->setRole('À définir'); // Rôle par défaut, l'utilisateur pourra le modifier
        $assignment->setAllocationRate(50); // Valeur par défaut
        $assignment->setAssignedFrom($project->getStartDate() ?? new \DateTime());
        $assignment->setAssignedTo($project->getEndDate() ?? new \DateTime('+3 months'));

        $em->persist($assignment);
        $em->flush();

        $this->addFlash('success', sprintf(
            '%s a été affecté(e) au projet. Pensez à ajuster le rôle et le taux d\'allocation.',
            $employee->getUsername()
        ));

        return $this->redirectToRoute('app_projectassignment_edit', [
            'idAssignment' => $assignment->getIdAssignment(),
        ]);
    }
}