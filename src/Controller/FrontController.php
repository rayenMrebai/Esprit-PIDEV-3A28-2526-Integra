<?php

namespace App\Controller;

use App\Entity\Inscription;
use App\Entity\Quiz_result;
use App\Entity\Training_program;
use App\Entity\UserAccount;
use App\Form\InscriptionType;
use App\Repository\JobpositionRepository;
use App\Repository\Quiz_resultRepository;
use App\Repository\SalaireRepository;
use App\Repository\SkillRepository;
use App\Repository\Training_programRepository;
use App\Service\CertificatGenerator;
use App\Service\CertificatMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FrontController extends AbstractController
{
    // ─── Accueil ──────────────────────────────────────────────────
    #[Route('/', name: 'front_acceuil')]
    public function acceuil(JobpositionRepository $jobpositionRepository): Response
    {
        $jobs = $jobpositionRepository->findBy([], ['postedAt' => 'DESC']);
        return $this->render('front/acceuil.html.twig', ['jobs' => $jobs]);
    }

    #[Route('/home', name: 'front_home')]
    public function home(JobpositionRepository $jobpositionRepository): Response
    {
        $jobs = $jobpositionRepository->findBy([], ['postedAt' => 'DESC']);
        return $this->render('front/home.html.twig', ['jobs' => $jobs]);
    }

    #[Route('/offre/{id}', name: 'front_job_show')]
    public function show(JobpositionRepository $jobpositionRepository, int $id): Response
    {
        $job = $jobpositionRepository->find($id);
        if (!$job) {
            throw $this->createNotFoundException('Offre non trouvée');
        }
        return $this->render('front/job_show.html.twig', ['job' => $job]);
    }

    // ─── Salaires ─────────────────────────────────────────────────
    #[Route('/front/mes-salaires', name: 'app_employee_salaires')]
    #[IsGranted('ROLE_USER')]
    public function mesSalaires(SalaireRepository $salaireRepo): Response
    {
        /** @var UserAccount $user */
        $user = $this->getUser();
        $salaires = $salaireRepo->findBy(['user' => $user], ['datePaiement' => 'DESC']);

        $totalPercu = 0;
        $totalBonus = 0;
        $nbPayes    = 0;

        foreach ($salaires as $salaire) {
            $totalPercu += $salaire->getTotalAmount();
            $totalBonus += $salaire->getBonusAmount();
            if ($salaire->getStatus() === 'PAYÉ') {
                $nbPayes++;
            }
        }

        return $this->render('front/mes_salaires.html.twig', [
            'user'       => $user,
            'salaires'   => $salaires,
            'totalPercu' => $totalPercu,
            'totalBonus' => $totalBonus,
            'nbPayes'    => $nbPayes,
            'moyenne'    => count($salaires) > 0 ? $totalPercu / count($salaires) : 0,
            'nbTotal'    => count($salaires),
        ]);
    }

    // ─── Formations ───────────────────────────────────────────────
    #[Route('/formations', name: 'app_frontoffice_trainings')]
    public function formations(
        Training_programRepository $trainingRepository,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        $inscriptionsExistantes = [];

        if ($user) {
            foreach ($em->getRepository(Inscription::class)->findBy(['user' => $user]) as $insc) {
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
        $quizExistante        = null;

        if ($user) {
            $inscriptionExistante = $em->getRepository(Inscription::class)
                ->findOneBy(['user' => $user, 'formation' => $training]);
            $quizExistante = $em->getRepository(Quiz_result::class)
                ->findOneBy(['user' => $user, 'training' => $training]);
        }

        return $this->render('frontoffice/training_show.html.twig', [
            'training'    => $training,
            'inscription' => $inscriptionExistante,
            'quiz'        => $quizExistante,
        ]);
    }

    // ─── Compétences ──────────────────────────────────────────────
    #[Route('/competences', name: 'app_frontoffice_skills')]
    #[IsGranted('ROLE_USER')]
    public function skills(): Response
    {
        /** @var UserAccount $user */
        $user = $this->getUser();
        return $this->render('frontoffice/skills.html.twig', [
            'userSkills' => $user->getSkills(),
        ]);
    }

    // ─── Quiz ─────────────────────────────────────────────────────
    #[Route('/quiz', name: 'app_frontoffice_quiz')]
    #[IsGranted('ROLE_USER')]
    public function quiz(Quiz_resultRepository $quizRepository): Response
    {
        $user = $this->getUser();
        return $this->render('frontoffice/quiz.html.twig', [
            'quizResults' => $quizRepository->findBy(['user' => $user]),
        ]);
    }

    #[Route('/quiz/{id}/take', name: 'app_frontoffice_quiz_take')]
    #[IsGranted('ROLE_USER')]
    public function quizTake(Quiz_result $quiz): Response
    {
        $user = $this->getUser();
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

    #[Route('/quiz/{id}/submit', name: 'app_frontoffice_quiz_submit', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function quizSubmit(
        Quiz_result $quiz,
        Request $request,
        EntityManagerInterface $em,
        CertificatMailer $certifMailer  // ← renommé
    ): Response {
        $user = $this->getUser();

        if ($quiz->getUser() !== $user) {
            throw $this->createAccessDeniedException('Ce quiz ne vous appartient pas.');
        }
        if ($quiz->getCompletedAt() !== null) {
            $this->addFlash('warning', 'Vous avez déjà complété ce quiz.');
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
            $this->addFlash('info', 'Tentative d\'envoi du certificat...');

            try {
                error_log('=== TENTATIVE ENVOI CERTIFICAT ===');
                error_log('Quiz ID: ' . $quiz->getId());
                error_log('User email: ' . $user->getEmail());
                error_log('Score: ' . $score . '/' . $totalQuestions);
                error_log('Percentage: ' . $percentage);

                $certifMailer->sendCertificat($quiz);  // ← corrigé

                error_log('✅ ENVOI RÉUSSI');

                $this->addFlash('success', sprintf(
                    'Quiz réussi ! Score : %d/%d (%.0f%%) ✅ — Certificat envoyé à %s',
                    $score, $totalQuestions, $percentage, $user->getEmail()
                ));
            } catch (\Exception $e) {
                error_log('❌ ERREUR ENVOI: ' . $e->getMessage());
                error_log('Trace: ' . $e->getTraceAsString());

                $this->addFlash('warning', sprintf(
                    'Quiz réussi ! Score : %d/%d (%.0f%%) ✅ — Erreur envoi email : %s',
                    $score, $totalQuestions, $percentage, $e->getMessage()
                ));
            }
        }

        return $this->redirectToRoute('app_frontoffice_quiz');
    }

    #[Route('/quiz/{id}/certificat', name: 'app_frontoffice_quiz_certificat')]
    #[IsGranted('ROLE_USER')]
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
        $fileName   = sprintf('certificat_%s.pdf', $this->slugify($quiz->getTraining()->getTitle()));

        return new Response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    // ─── Inscriptions ─────────────────────────────────────────────
    #[Route('/inscription/formations', name: 'app_frontoffice_inscription_formations')]
    #[IsGranted('ROLE_USER')]
    public function inscriptionsFormationsDisponibles(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $formationsDisponibles = $em->getRepository(Training_program::class)
            ->createQueryBuilder('f')
            ->where('f.status IN (:status)')
            ->setParameter('status', ['PROGRAMMÉ', 'EN COURS'])
            ->orderBy('f.startDate', 'ASC')
            ->getQuery()
            ->getResult();

        $formationsInscrites = [];
        foreach ($em->getRepository(Inscription::class)->findBy(['user' => $user]) as $insc) {
            $formationsInscrites[$insc->getFormation()->getId()] = $insc->getStatus();
        }

        return $this->render('frontoffice/inscription/formations.html.twig', [
            'formations'   => $formationsDisponibles,
            'inscriptions' => $formationsInscrites,
        ]);
    }

    #[Route('/inscription/formation/{id}/new', name: 'app_frontoffice_inscription_new')]
    #[IsGranted('ROLE_USER')]
    public function inscriptionNew(
        Training_program $formation,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();

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
    #[IsGranted('ROLE_USER')]
    public function mesInscriptions(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

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

    // ─── Utilitaire ───────────────────────────────────────────────
    private function slugify(string $text): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $text), '_'));
    }
}