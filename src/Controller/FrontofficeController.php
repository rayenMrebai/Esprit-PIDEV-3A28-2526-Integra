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
    public function index(): Response
    {
        return $this->render('frontoffice/index.html.twig');
    }
    
   #[Route('/formations', name: 'app_frontoffice_trainings')]
public function trainings(Training_programRepository $trainingRepository): Response
{
    return $this->render('frontoffice/training_program.html.twig', [
        'trainings' => $trainingRepository->findAll(),
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