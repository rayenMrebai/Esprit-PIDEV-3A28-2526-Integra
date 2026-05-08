<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
use App\Repository\ProjectassignmentRepository;
use App\Service\PdfExportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ProjectExcelExportService;
use App\Repository\UserAccountRepository;

#[Route('/project')]
final class ProjectController extends AbstractController
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private ProjectassignmentRepository $assignmentRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route(name: 'app_project_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->get('search');
        $status = $request->query->get('status');
        $startFrom = $request->query->get('startFrom');
        $startTo = $request->query->get('startTo');

        $queryBuilder = $this->projectRepository->createQueryBuilder('p');

        if ($search) {
            $queryBuilder->andWhere('p.name LIKE :search OR p.projectId LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        if ($status) {
            $queryBuilder->andWhere('p.status = :status')
                ->setParameter('status', $status);
        }
        // On s'assure que $startFrom est une chaîne non vide avant de l'utiliser
        if ($startFrom && is_string($startFrom)) {
            $queryBuilder->andWhere('p.startDate >= :startFrom')
                ->setParameter('startFrom', new \DateTime($startFrom));
        }
        // Idem pour $startTo
        if ($startTo && is_string($startTo)) {
            $queryBuilder->andWhere('p.startDate <= :startTo')
                ->setParameter('startTo', new \DateTime($startTo));
        }

        $projects = $queryBuilder->orderBy('p.projectId', 'ASC')->getQuery()->getResult();

        // Comptage des employés par projet
        $employeesCount = [];
        foreach ($projects as $project) {
            $count = $this->assignmentRepository->count(['project' => $project]);
            $employeesCount[$project->getProjectId()] = $count;
        }

        return $this->render('project/index.html.twig', [
            'projects' => $projects,
            'employeesCount' => $employeesCount,
        ]);
    }

    #[Route('/new', name: 'app_project_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($project);
            $this->entityManager->flush();
            $this->addFlash('success', 'Projet créé avec succès.');
            return $this->redirectToRoute('app_project_index');
        }

        return $this->render('project/new.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{projectId}/edit', name: 'app_project_edit', methods: ['GET', 'POST'])]
    public function edit(int $projectId, Request $request): Response
    {
        $project = $this->projectRepository->find($projectId);
        if (!$project) {
            throw $this->createNotFoundException('Projet non trouvé');
        }

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Projet modifié avec succès.');
            return $this->redirectToRoute('app_project_index');
        }

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{projectId}', name: 'app_project_delete', methods: ['POST'])]
    public function delete(int $projectId, Request $request): Response
    {
        $project = $this->projectRepository->find($projectId);
        if (!$project) {
            throw $this->createNotFoundException('Projet non trouvé');
        }

        if ($this->isCsrfTokenValid('delete' . $projectId, $request->getPayload()->getString('_token'))) {
            $this->entityManager->remove($project);
            $this->entityManager->flush();
            $this->addFlash('success', 'Projet supprimé.');
        }

        return $this->redirectToRoute('app_project_index');
    }

    // ========== EXPORTS PDF ==========

    #[Route('/export/all', name: 'app_project_export_all')]
    public function exportAll(PdfExportService $pdfService): Response
    {
        $projects = $this->projectRepository->findAll();
        $assignmentsByProject = [];
        foreach ($projects as $p) {
            $assignmentsByProject[$p->getProjectId()] = $this->assignmentRepository->findBy(['project' => $p]);
        }

        $html = $pdfService->renderAllProjectsHtml($projects, $assignmentsByProject);
        return $pdfService->generateProjectPdfResponse($html, 'rapport_projets_' . date('Ymd_His') . '.pdf');
    }

    #[Route('/{projectId}/export', name: 'app_project_export_single')]
    public function exportSingle(int $projectId, PdfExportService $pdfService): Response
    {
        $project = $this->projectRepository->find($projectId);
        if (!$project) {
            throw $this->createNotFoundException('Projet non trouvé');
        }

        $assignments = $this->assignmentRepository->findBy(['project' => $project]);
        $html = $pdfService->renderSingleProjectHtml($project, $assignments);
        return $pdfService->generateProjectPdfResponse($html, 'projet_' . $projectId . '_' . date('Ymd_His') . '.pdf');
    }

    #[Route('/export/excel/all', name: 'app_project_export_excel_all')]
    public function exportExcelAll(ProjectExcelExportService $excelService, UserAccountRepository $userRepo): Response
    {
        $projects = $this->projectRepository->findAll();
        $assignments = $this->assignmentRepository->findAll();
        $employees = $userRepo->findAll();

        return $excelService->exportAllData($projects, $assignments, $employees);
    }

    #[Route('/{projectId}/export/excel', name: 'app_project_export_excel_single')]
    public function exportExcelSingle(int $projectId, ProjectExcelExportService $excelService, UserAccountRepository $userRepo): Response
    {
        $project = $this->projectRepository->find($projectId);
        if (!$project) {
            throw $this->createNotFoundException('Projet non trouvé');
        }
        $assignments = $this->assignmentRepository->findBy(['project' => $project]);
        $employees = $userRepo->findAll();

        return $excelService->exportSingleProject($project, $assignments, $employees);
    }
}