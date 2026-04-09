<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use App\Repository\ProjectassignmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/front')]
class FrontController extends AbstractController
{
    #[Route('', name: 'front_home')]
    public function home(ProjectRepository $projectRepo): Response
    {
        $latestProjects = $projectRepo->findBy([], ['startDate' => 'DESC'], 3);
        return $this->render('front/home.html.twig', ['latestProjects' => $latestProjects]);
    }

    #[Route('/projects', name: 'front_projects')]
    public function projects(ProjectRepository $projectRepo): Response
    {
        $projects = $projectRepo->findBy([], ['startDate' => 'DESC']);
        return $this->render('front/project/index.html.twig', ['projects' => $projects]);
    }

    #[Route('/project/{projectId}', name: 'front_project_show')]
    public function showProject(int $projectId, ProjectRepository $projectRepo, ProjectassignmentRepository $assignmentRepo): Response
    {
        $project = $projectRepo->find($projectId);
        if (!$project) throw $this->createNotFoundException();
        $assignments = $assignmentRepo->findBy(['project' => $project]);
        return $this->render('front/project/show.html.twig', [
            'project' => $project,
            'assignments' => $assignments,
        ]);
    }
}