<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\SkillRepository;
use App\Repository\Training_programRepository;
use App\Repository\Quiz_resultRepository;

#[Route('/backoffice', name: 'app_backoffice_')]
class BackofficeController extends AbstractController
{
    #[Route('', name: 'dashboard')]
    public function dashboard(): Response
    {
        return $this->render('backoffice/index.html.twig');
    }

    #[Route('/formations', name: 'formations')]
    public function formations(
        SkillRepository $skillRepository,
        Training_programRepository $trainingProgramRepository,
        Quiz_resultRepository $quizResultRepository
    ): Response
    {
        return $this->render('backoffice/formations.html.twig', [
            'skills' => $skillRepository->findAll(),
            'trainingPrograms' => $trainingProgramRepository->findAll(),
            'quizResults' => $quizResultRepository->findAll(),
        ]);
    }
}