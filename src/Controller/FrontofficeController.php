<?php
// src/Controller/FrontofficeController.php

namespace App\Controller;

use App\Entity\Inscription;
use App\Entity\Quiz_result;
use App\Entity\Training_program;
use App\Form\InscriptionType;
use App\Repository\Quiz_resultRepository;
use App\Repository\SkillRepository;
use App\Repository\Training_programRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\CertificatGenerator;  // ✅ ajouter
use App\Service\CertificatMailer; 

#[Route('/frontoffice')]
class FrontofficeController extends AbstractController
{
    #[Route('/', name: 'app_frontoffice_dashboard')]
    public function index(
        Training_programRepository $trainingRepository,
        SkillRepository $skillRepository,
        Quiz_resultRepository $quizRepository
    ): Response {
        $user = $this->getUser();

        $quizPassed = 0;
        if ($user) {
            // ✅ Filtrer par utilisateur connecté, pas findAll()
            $userQuizResults = $quizRepository->findBy(['user' => $user]);
            $quizPassed = count(array_filter($userQuizResults, fn($q) => $q->isPassed()));
        }

        return $this->render('frontoffice/dashboard.html.twig', [
            'trainings_count' => $trainingRepository->count([]),
            'skills_count'    => $skillRepository->count([]),
            'quiz_count'      => $user ? $quizRepository->count(['user' => $user]) : 0,
            'quiz_passed'     => $quizPassed,
            'latestTrainings' => $trainingRepository->findBy([], ['startDate' => 'DESC'], 3),
        ]);
    }

    #[Route('/formations', name: 'app_frontoffice_trainings')]
    public function formations(
        Training_programRepository $trainingRepository,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();

        $inscriptionsExistantes = [];
        if ($user) {
            $inscriptions = $em->getRepository(Inscription::class)
                ->findBy(['user' => $user]);
            foreach ($inscriptions as $insc) {
                $inscriptionsExistantes[$insc->getFormation()->getId()] = $insc->getStatus();
            }
        }

        return $this->render('frontoffice/trainings.html.twig', [
            'trainings'    => $trainingRepository->findAll(),
            'inscriptions' => $inscriptionsExistantes,
        ]);
    }

    #[Route('/formations/{id}', name: 'app_frontoffice_training_show')]
    public function trainingShow(
        Training_programRepository $trainingRepository,
        EntityManagerInterface $em,
        int $id
    ): Response {
        $training = $trainingRepository->find($id);

        if (!$training) {
            throw $this->createNotFoundException('Formation introuvable.');
        }

        $user = $this->getUser();
        $inscriptionExistante = null;
        $quizExistante = null;

        if ($user) {
            $inscriptionExistante = $em->getRepository(Inscription::class)
                ->findOneBy(['user' => $user, 'formation' => $training]);

            // ✅ ['user' => $user] fonctionne maintenant
            $quizExistante = $em->getRepository(Quiz_result::class)
                ->findOneBy(['user' => $user, 'training' => $training]);
        }

        return $this->render('frontoffice/training_show.html.twig', [
            'training'    => $training,
            'inscription' => $inscriptionExistante,
            'quiz'        => $quizExistante,
        ]);
    }

    #[Route('/competences', name: 'app_frontoffice_skills')]
    public function skills(): Response
    {
        /** @var \App\Entity\UserAccount $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('frontoffice/skills.html.twig', [
            'userSkills' => $user->getSkills(),
        ]);
    }

    #[Route('/quiz', name: 'app_frontoffice_quiz')]
    public function quiz(Quiz_resultRepository $quizRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // ✅ ['user' => $user] fonctionne maintenant
        $quizResults = $quizRepository->findBy(['user' => $user]);

        return $this->render('frontoffice/quiz.html.twig', [
            'quizResults' => $quizResults,
        ]);
    }

    #[Route('/inscription/formations', name: 'app_frontoffice_inscription_formations')]
    public function inscriptionsFormationsDisponibles(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $formationsDisponibles = $em->getRepository(Training_program::class)
            ->createQueryBuilder('f')
            ->where('f.status IN (:status)')
            ->setParameter('status', ['PROGRAMMÉ', 'EN COURS'])
            ->orderBy('f.startDate', 'ASC')
            ->getQuery()
            ->getResult();

        $inscriptionsExistantes = $em->getRepository(Inscription::class)
            ->findBy(['user' => $user]);

        $formationsInscrites = [];
        foreach ($inscriptionsExistantes as $insc) {
            $formationsInscrites[$insc->getFormation()->getId()] = $insc->getStatus();
        }

        return $this->render('frontoffice/inscription/formations.html.twig', [
            'formations'   => $formationsDisponibles,
            'inscriptions' => $formationsInscrites,
        ]);
    }

    #[Route('/inscription/formation/{id}/new', name: 'app_frontoffice_inscription_new')]
    public function inscriptionNew(
        Training_program $formation,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $inscriptionExistante = $em->getRepository(Inscription::class)
            ->findOneBy(['user' => $user, 'formation' => $formation]);

        if ($inscriptionExistante) {
            $this->addFlash('warning', 'Vous avez déjà une demande d\'inscription pour cette formation.');
            return $this->redirectToRoute('app_frontoffice_inscription_formations');
        }

        if (!in_array($formation->getStatus(), ['PROGRAMMÉ', 'EN COURS'])) {
            $this->addFlash('error', 'Cette formation n\'est pas disponible pour inscription.');
            return $this->redirectToRoute('app_frontoffice_inscription_formations');
        }

        $inscription = new Inscription();
        $inscription->setUser($user);
        $inscription->setFormation($formation);

        $form = $this->createForm(InscriptionType::class, $inscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($inscription);
            $em->flush();

            $this->addFlash('success', 'Votre demande d\'inscription a été envoyée.');
            return $this->redirectToRoute('app_frontoffice_inscription_formations');
        }

        return $this->render('frontoffice/inscription/new.html.twig', [
            'formation' => $formation,
            'form'      => $form->createView(),
        ]);
    }

    #[Route('/mes-inscriptions', name: 'app_frontoffice_mes_inscriptions')]
    public function mesInscriptions(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $inscriptions = $em->getRepository(Inscription::class)
            ->createQueryBuilder('i')
            ->where('i.user = :user')
            ->orderBy('i.dateDemande', 'DESC')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        return $this->render('frontoffice/inscription/mes_inscriptions.html.twig', [
            'inscriptions' => $inscriptions,
        ]);
    }

    #[Route('/quiz/{id}/take', name: 'app_frontoffice_quiz_take')]
    public function quizTake(Quiz_result $quiz): Response
    {
        $user = $this->getUser();

        // ✅ Comparaison via l'objet relation
        if ($quiz->getUser() !== $user) {
            throw $this->createAccessDeniedException('Ce quiz ne vous appartient pas.');
        }

        if ($quiz->getCompletedAt() !== null) {
            $this->addFlash('warning', 'Vous avez déjà complété ce quiz.');
            return $this->redirectToRoute('app_frontoffice_quiz');
        }

        return $this->render('frontoffice/quiz/take.html.twig', [
            'quiz'      => $quiz,
            'questions' => $quiz->getQuestions(),
        ]);
    }

    // FrontofficeController.php — ajouter le service dans le constructeur ou la méthode

#[Route('/quiz/{id}/submit', name: 'app_frontoffice_quiz_submit', methods: ['POST'])]
public function quizSubmit(
    Quiz_result $quiz,
    Request $request,
    EntityManagerInterface $em,
    CertificatMailer $certificatMailer
): Response {
    $user = $this->getUser();

    if ($quiz->getUser() !== $user) {
        throw $this->createAccessDeniedException('Ce quiz ne vous appartient pas.');
    }

    if ($quiz->getCompletedAt() !== null) {
        $this->addFlash('warning', 'Vous avez deja complete ce quiz.');
        return $this->redirectToRoute('app_frontoffice_quiz');
    }

    $questions      = $quiz->getQuestions();
    $score          = 0;

    foreach ($questions as $i => $question) {
        $userAnswer = $request->request->get('question_' . $i);
        if ($userAnswer !== null && (int)$userAnswer === $question['correct']) {
            $score++;
        }
    }

    $totalQuestions = count($questions);
    $percentage     = $totalQuestions > 0 ? ($score / $totalQuestions) * 100 : 0;
    $passed         = $percentage >= 60;

    $quiz->setScore($score);
    $quiz->setTotalQuestions($totalQuestions);
    $quiz->setPercentage($percentage);
    $quiz->setPassed($passed);
    $quiz->setCompletedAt(new \DateTime());

    $em->flush();

    if ($passed) {
        try {
            $certificatMailer->sendCertificat($quiz);
            $this->addFlash('success', sprintf(
                'Quiz reussi ! Score : %d/%d (%.0f%%) - Votre certificat a ete envoye a %s',
                $score,
                $totalQuestions,
                $percentage,
                $user->getEmail()  // ✅ affiche l'email destinataire dans le flash
            ));
        } catch (\Exception $e) {
            $this->addFlash('warning', sprintf(
                'Quiz reussi ! Score : %d/%d (%.0f%%) - Erreur envoi email : %s',
                $score, $totalQuestions, $percentage, $e->getMessage()
            ));
        }
    } else {
        $this->addFlash('warning', sprintf(
            'Quiz echoue. Score : %d/%d (%.0f%%) - Score minimum requis : 60%%.',
            $score, $totalQuestions, $percentage
        ));
    }

    return $this->redirectToRoute('app_frontoffice_quiz');
}
// FrontofficeController.php — ajouter cette route

#[Route('/quiz/{id}/certificat', name: 'app_frontoffice_quiz_certificat')]
public function downloadCertificat(
    Quiz_result $quiz,
    CertificatGenerator $certificatGenerator
): Response {
    $user = $this->getUser();

    if ($quiz->getUser() !== $user) {
        throw $this->createAccessDeniedException();
    }

    if (!$quiz->isPassed()) {
        $this->addFlash('error', 'Vous devez réussir le quiz pour obtenir le certificat.');
        return $this->redirectToRoute('app_frontoffice_quiz');
    }

    $pdfContent = $certificatGenerator->generate($quiz);
    $fileName   = sprintf(
        'certificat_%s.pdf',
        $this->slugify($quiz->getTraining()->getTitle())
    );

    return new Response($pdfContent, 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
    ]);
}

private function slugify(string $text): string
{
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $text), '_'));
}
}