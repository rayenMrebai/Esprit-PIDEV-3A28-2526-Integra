<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Projectassignment;
use App\Form\ProjectassignmentType;
use App\Repository\ProjectassignmentRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/projectassignment')]
final class ProjectassignmentController extends AbstractController
{
    public function __construct(
        private ProjectassignmentRepository $assignmentRepository,
        private ProjectRepository $projectRepository
    ) {
    }

    #[Route(name: 'app_projectassignment_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Récupération des paramètres GET avec cast pour garantir le type string
        $searchTerm = (string) $request->query->get('search', '');
        $selectedProject = $request->query->get('project') ? (int) $request->query->get('project') : null;
        $selectedRole = (string) $request->query->get('role', '');

        $assignments = $this->assignmentRepository->findByFilters($searchTerm, $selectedProject, $selectedRole);

        // Récupération de tous les projets pour le filtre déroulant
        $projects = $this->projectRepository->findAll();

        // Récupération des rôles distincts pour le filtre déroulant
        $roles = $this->assignmentRepository->findDistinctRoles();

        return $this->render('projectassignment/index.html.twig', [
            'assignments' => $assignments,
            'projects' => $projects,
            'roles' => $roles,
            'searchTerm' => $searchTerm,
            'selectedProject' => $selectedProject,
            'selectedRole' => $selectedRole,
        ]);
    }

    #[Route('/new', name: 'app_projectassignment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $assignment = new Projectassignment();
        $form = $this->createForm(ProjectassignmentType::class, $assignment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($assignment);
            $entityManager->flush();
            $this->addFlash('success', 'Affectation créée avec succès.');
            return $this->redirectToRoute('app_projectassignment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('projectassignment/new.html.twig', [
            'assignment' => $assignment,
            'form' => $form,
        ]);
    }

    #[Route('/{idAssignment}', name: 'app_projectassignment_show', methods: ['GET'])]
    public function show(Projectassignment $assignment): Response
    {
        return $this->render('projectassignment/show.html.twig', [
            'assignment' => $assignment,
        ]);
    }

    #[Route('/{idAssignment}/edit', name: 'app_projectassignment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Projectassignment $assignment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProjectassignmentType::class, $assignment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Affectation modifiée avec succès.');
            return $this->redirectToRoute('app_projectassignment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('projectassignment/edit.html.twig', [
            'assignment' => $assignment,
            'form' => $form,
        ]);
    }

    #[Route('/{idAssignment}', name: 'app_projectassignment_delete', methods: ['POST'])]
    public function delete(Request $request, Projectassignment $assignment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$assignment->getIdAssignment(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($assignment);
            $entityManager->flush();
            $this->addFlash('success', 'Affectation supprimée.');
        }
        return $this->redirectToRoute('app_projectassignment_index', [], Response::HTTP_SEE_OTHER);
    }
}