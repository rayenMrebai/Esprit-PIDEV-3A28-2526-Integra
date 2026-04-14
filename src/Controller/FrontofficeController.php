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
    // ─── Dashboard ────────────────────────────────────────────────────────────

    #[Route('/', name: 'app_frontoffice_dashboard')]
    public function index(
        Training_programRepository $trainingRepository,
        SkillRepository $skillRepository,
        Quiz_resultRepository $quizRepository
    ): Response {
        // Quiz réussis (passed = true) pour l'utilisateur connecté
        $allQuizResults = $quizRepository->findAll();
        $quizPassed = count(array_filter($allQuizResults, fn($q) => $q->isPassed()));

        return $this->render('frontoffice/dashboard.html.twig', [
            'trainings_count'  => $trainingRepository->count([]),
            'skills_count'     => $skillRepository->count([]),
            'quiz_count'       => $quizRepository->count([]),
            'quiz_passed'      => $quizPassed,                         // ← nouveau : quiz réussis
            'latestTrainings'  => $trainingRepository->findBy(         // ← nouveau : 3 dernières formations
                [],
                ['startDate' => 'DESC'],
                3
            ),
        ]);
    }

    // ─── Formations (liste) ───────────────────────────────────────────────────

    #[Route('/formations', name: 'app_frontoffice_trainings')]
    public function formations(
        Training_programRepository $trainingRepository
    ): Response {
        return $this->render('frontoffice/trainings.html.twig', [
            'trainings' => $trainingRepository->findAll(),
        ]);
    }

    // ─── Formation (détail) ───────────────────────────────────────────────────

    #[Route('/formations/{id}', name: 'app_frontoffice_training_show')]
    public function trainingShow(
        Training_programRepository $trainingRepository,
        int $id
    ): Response {
        $training = $trainingRepository->find($id);

        if (!$training) {
            throw $this->createNotFoundException('Formation introuvable.');
        }

        return $this->render('frontoffice/training_show.html.twig', [
            'training' => $training,
        ]);
    }

    // ─── Compétences ──────────────────────────────────────────────────────────

    #[Route('/competences', name: 'app_frontoffice_skills')]
    public function skills(): Response
    {
        /** @var \App\Entity\UserAccount $user */
        $user = $this->getUser();

        // Sécurité : rediriger si non connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('frontoffice/skills.html.twig', [
            'userSkills' => $user->getSkills(),
        ]);
    }

    // ─── Quiz ─────────────────────────────────────────────────────────────────

    #[Route('/quiz', name: 'app_frontoffice_quiz')]
    public function quiz(Quiz_resultRepository $quizRepository): Response
    {
        return $this->render('frontoffice/quiz.html.twig', [
            'quizResults' => $quizRepository->findAll(),
        ]);
    }
}