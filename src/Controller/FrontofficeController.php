<?php

namespace App\Controller;

use App\Repository\Quiz_resultRepository;
use App\Repository\SkillRepository;
use App\Repository\Training_programRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/frontoffice')]
class FrontofficeController extends AbstractController
{
    #[Route('/', name: 'app_frontoffice_index')]
    public function index(
        Training_programRepository $trainingRepository,
        SkillRepository $skillRepository,
        Quiz_resultRepository $quizRepository
    ): Response {
        return $this->render('frontoffice/index.html.twig', [
            'trainings_count' => $trainingRepository->count([]),
            'skills_count' => $skillRepository->count([]),
            'quiz_count' => $quizRepository->count([]),
            'projets_count' => 0,
        ]);
    }
    
    #[Route('/formations', name: 'app_frontoffice_trainings')]
    public function formations(
        Training_programRepository $trainingRepository,
        SkillRepository $skillRepository,
        Quiz_resultRepository $quizRepository
    ): Response {
        return $this->render('frontoffice/training_program.html.twig', [
            'trainings' => $trainingRepository->findAll(),
            'skills' => $skillRepository->findAll(),
            'quizResults' => $quizRepository->findAll(),
        ]);
    }
    
    #[Route('/competences', name: 'app_frontoffice_skills')]
    public function skills(SkillRepository $skillRepository): Response
    {
        return $this->render('frontoffice/skills.html.twig', [
            'skills' => $skillRepository->findAll(),
        ]);
    }
    
    #[Route('/quiz', name: 'app_frontoffice_quiz')]
    public function quiz(Quiz_resultRepository $quizRepository): Response
    {
        return $this->render('frontoffice/quiz.html.twig', [
            'quizResults' => $quizRepository->findAll(),
        ]);
    }
}