<?php

declare(strict_types=1);

// src/Controller/BackofficeformationController.php

namespace App\Controller;

use App\Entity\FormationSession;
use App\Form\FormationSessionType;
use App\Entity\UserAccount;
use App\Entity\Training_program;
use App\Entity\Skill;
use App\Entity\Quiz_result;
use App\Entity\Inscription;
use App\Form\SkillType;
use App\Form\ProfileFormType;
use App\Form\TrainingProgramType;
use App\Form\UserEditFormType;
use App\Service\AIQuizGenerator;
use App\Repository\UserAccountRepository;
use App\Repository\Training_programRepository;
use App\Repository\SkillRepository;
use App\Repository\Quiz_resultRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/backoffice')]
#[IsGranted('ROLE_ADMIN')]
class BackofficeformationController extends AbstractController
{
    // ========== DASHBOARD ==========
    #[Route('/', name: 'app_backoffice_dashboard')]
    public function dashboard(
        UserAccountRepository $userRepo,
        Training_programRepository $trainingRepo,
        SkillRepository $skillRepo,
        Quiz_resultRepository $quizRepo
    ): Response {
        return $this->render('backoffice/index.html.twig', [
            'total_users'      => $userRepo->count([]),
            'total_formations' => $trainingRepo->count([]),
            'total_competences'=> $skillRepo->count([]),
            'total_quiz'       => $quizRepo->count([]),
            'recent_users'     => $userRepo->findBy([], ['userId' => 'DESC'], 5),
        ]);
    }

    // ========== GESTION DES UTILISATEURS ==========
    #[Route('/users', name: 'app_backoffice_users')]
    public function usersList(UserAccountRepository $repo, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $role = $request->query->get('role', '');

        $qb = $repo->createQueryBuilder('u');
        if ($search) {
            $qb->andWhere('u.username LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        if ($role) {
            $qb->andWhere('u.role = :role')->setParameter('role', $role);
        }
        $users = $qb->getQuery()->getResult();

        return $this->render('backoffice/admin/user_list.html.twig', [
            'users' => $users,
            'search' => $search,
            'selectedRole' => $role,
        ]);
    }

    #[Route('/users/new', name: 'app_backoffice_users_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function userNew(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($request->isMethod('POST')) {
            // Récupération sécurisée en string
            $username = (string) $request->request->get('username');
            $email    = (string) $request->request->get('email');
            $password = (string) $request->request->get('password');
            $role     = (string) $request->request->get('role');

            $user = new UserAccount();
            $user->setUsername($username);
            $user->setEmail($email);
            $user->setPasswordHash($passwordHasher->hashPassword($user, $password));
            $user->setRole($role);
            $user->setIsActive(true);
            $user->setAccountStatus('ACTIVE');

            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur ajouté avec succès.');
            return $this->redirectToRoute('app_backoffice_users');
        }

        return $this->render('backoffice/admin/add_user.html.twig');
    }

    #[Route('/user/{id}', name: 'app_backoffice_users_show')]
    public function userShow(UserAccount $user): Response
    {
        return $this->render('backoffice/admin/user_show.html.twig', ['user' => $user]);
    }

    #[Route('/user/{id}/edit', name: 'app_backoffice_users_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function userEdit(UserAccount $user, Request $request, EntityManagerInterface $em, SkillRepository $skillRepo): Response
    {
        $form = $this->createForm(UserEditFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // all() retourne toujours un tableau, jamais null
            $skillIds = $request->request->all('skills');
            $skills = $skillRepo->findBy(['id' => $skillIds]);

            foreach ($user->getSkills() as $oldSkill) {
                $user->removeSkill($oldSkill);
            }

            foreach ($skills as $skill) {
                $user->addSkill($skill);
            }

            $em->flush();
            $this->addFlash('success', 'Utilisateur modifié avec succès.');
            return $this->redirectToRoute('app_backoffice_users');
        }

        $allSkills = $skillRepo->findAll();

        return $this->render('backoffice/admin/user_edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'allSkills' => $allSkills,
        ]);
    }

    #[Route('/user/{id}/toggle', name: 'app_backoffice_users_toggle')]
    public function userToggle(UserAccount $user, EntityManagerInterface $em): Response
    {
        $user->setIsActive(!$user->getIsActive());
        $em->flush();
        $this->addFlash('success', 'Statut utilisateur modifié.');
        return $this->redirectToRoute('app_backoffice_users');
    }

    #[Route('/user/{id}/delete', name: 'app_backoffice_users_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function userDelete(UserAccount $user, EntityManagerInterface $em, Request $request): Response
    {
        $token = (string) $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete'.$user->getUserId(), $token)) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        }
        return $this->redirectToRoute('app_backoffice_users');
    }

    // ========== GESTION DES FORMATIONS ==========
    #[Route('/formations', name: 'app_backoffice_formations')]
    public function formationsList(Training_programRepository $repo, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $type = $request->query->get('type', '');
        $status = $request->query->get('status', '');

        $qb = $repo->createQueryBuilder('t');

        if ($search) {
            $qb->andWhere('t.title LIKE :search OR t.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        if ($type) {
            $qb->andWhere('t.type = :type')->setParameter('type', $type);
        }
        if ($status) {
            $qb->andWhere('t.status = :status')->setParameter('status', $status);
        }

        $formations = $qb->getQuery()->getResult();

        return $this->render('backoffice/training_program/index.html.twig', [
            'training_programs' => $formations,
            'search' => $search,
            'selectedType' => $type,
            'selectedStatus' => $status,
        ]);
    }

    #[Route('/formations/new', name: 'app_backoffice_formations_new')]
    public function formationNew(Request $request, EntityManagerInterface $em): Response
    {
        $formation = new Training_program();
        $form = $this->createForm(TrainingProgramType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($formation);
            $em->flush();
            $this->addFlash('success', 'Formation créée avec succès.');
            return $this->redirectToRoute('app_backoffice_formations');
        }

        return $this->render('backoffice/training_program/new.html.twig', [
            'form' => $form->createView(),
            'training_program' => $formation,
        ]);
    }

    #[Route('/formation/{id}', name: 'app_backoffice_formations_show')]
    public function formationShow(Training_program $formation): Response
    {
        return $this->render('backoffice/training_program/show.html.twig', [
            'training_program' => $formation
        ]);
    }

    #[Route('/formation/{id}/edit', name: 'app_backoffice_formations_edit')]
    public function formationEdit(Training_program $formation, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TrainingProgramType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Formation modifiée avec succès.');
            return $this->redirectToRoute('app_backoffice_formations');
        }

        return $this->render('backoffice/training_program/edit.html.twig', [
            'training_program' => $formation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/formation/{id}/delete', name: 'app_backoffice_formations_delete')]
    public function formationDelete(Training_program $formation, EntityManagerInterface $em, Request $request): Response
    {
        $token = (string) $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete'.$formation->getId(), $token)) {
            $em->remove($formation);
            $em->flush();
            $this->addFlash('success', 'Formation supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_backoffice_formations');
    }

    // ========== GESTION DES COMPÉTENCES ==========
    #[Route('/competences', name: 'app_backoffice_competences')]
    public function competencesList(SkillRepository $repo, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $categorie = $request->query->get('categorie', '');
        $level = $request->query->get('level', '');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;

        $qb = $repo->createQueryBuilder('s');

        if ($search) {
            $qb->andWhere('s.nom LIKE :search OR s.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        if ($categorie) {
            $qb->andWhere('s.categorie = :categorie')->setParameter('categorie', $categorie);
        }
        if ($level) {
            $qb->andWhere('s.level_required = :level')->setParameter('level', $level);
        }

        $total = count($qb->getQuery()->getResult());
        $totalPages = (int) ceil($total / $limit);

        $skills = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('backoffice/skill/index.html.twig', [
            'skills' => $skills,
            'search' => $search,
            'selectedCategorie' => $categorie,
            'selectedLevel' => $level,
            'page' => $page,
            'total_pages' => $totalPages,
        ]);
    }

    #[Route('/competences/new', name: 'app_backoffice_competences_new')]
    public function competenceNew(Request $request, EntityManagerInterface $em): Response
    {
        $competence = new Skill();
        $form = $this->createForm(SkillType::class, $competence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($competence);
            $em->flush();
            $this->addFlash('success', 'Compétence créée avec succès.');
            return $this->redirectToRoute('app_backoffice_competences');
        }

        return $this->render('backoffice/skill/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/competence/{id}', name: 'app_backoffice_competences_show')]
    public function competenceShow(Skill $competence): Response
    {
        return $this->render('backoffice/skill/show.html.twig', [
            'skill' => $competence
        ]);
    }

    #[Route('/competence/{id}/edit', name: 'app_backoffice_competences_edit')]
    public function competenceEdit(Skill $competence, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(SkillType::class, $competence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Compétence modifiée avec succès.');
            return $this->redirectToRoute('app_backoffice_competences');
        }

        return $this->render('backoffice/skill/edit.html.twig', [
            'skill' => $competence,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/competence/{id}/delete', name: 'app_backoffice_competences_delete')]
    public function competenceDelete(Skill $competence, EntityManagerInterface $em): Response
    {
        $em->remove($competence);
        $em->flush();
        $this->addFlash('success', 'Compétence supprimée avec succès.');
        return $this->redirectToRoute('app_backoffice_competences');
    }

    // ========== GESTION DES QUIZ ==========
    #[Route('/quiz', name: 'app_backoffice_quiz')]
    public function quizList(Quiz_resultRepository $repo): Response
    {
        return $this->render('backoffice/quiz_result/index.html.twig', [
            'quiz_results' => $repo->findAll(),
        ]);
    }

    #[Route('/quiz/new', name: 'app_backoffice_quiz_new')]
    public function quizNew(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $quiz = new Quiz_result();

            // score peut être null ou int
            $scoreRaw = $request->request->get('score');
            $score = null !== $scoreRaw ? (int) $scoreRaw : null;
            $quiz->setScore($score);
            $quiz->setCompletedAt(new \DateTime());

            $em->persist($quiz);
            $em->flush();
            $this->addFlash('success', 'Quiz créé avec succès.');
            return $this->redirectToRoute('app_backoffice_quiz');
        }

        return $this->render('backoffice/quiz/new.html.twig');
    }

    #[Route('/quiz/{id}', name: 'app_backoffice_quiz_show')]
    public function quizShow(Quiz_result $quiz): Response
    {
        return $this->render('backoffice/quiz/show.html.twig', [
            'quiz_result' => $quiz
        ]);
    }

    #[Route('/quiz/{id}/edit', name: 'app_backoffice_quiz_edit')]
    public function quizEdit(Quiz_result $quiz, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $scoreRaw = $request->request->get('score');
            $score = null !== $scoreRaw ? (int) $scoreRaw : null;
            $quiz->setScore($score);

            $em->flush();
            $this->addFlash('success', 'Quiz modifié avec succès.');
            return $this->redirectToRoute('app_backoffice_quiz');
        }

        return $this->render('backoffice/quiz/edit.html.twig', [
            'quiz_result' => $quiz
        ]);
    }

    #[Route('/quiz/{id}/delete', name: 'app_backoffice_quiz_delete')]
    public function quizDelete(Quiz_result $quiz, EntityManagerInterface $em): Response
    {
        $em->remove($quiz);
        $em->flush();
        $this->addFlash('success', 'Quiz supprimé avec succès.');
        return $this->redirectToRoute('app_backoffice_quiz');
    }

    // ========== MON PROFIL ==========
    #[Route('/profile', name: 'app_backoffice_profile')]
    public function profile(): Response
    {
        return $this->render('backoffice/profile/show.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/profile/edit', name: 'app_backoffice_profile_edit')]
    public function profileEdit(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('app_backoffice_profile');
        }

        return $this->render('backoffice/profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ========== PLANNING DES FORMATIONS ==========
    #[Route('/planning', name: 'app_backoffice_planning')]
    public function planning(Request $request, EntityManagerInterface $em): Response
    {
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');
        $month = $request->query->get('month', date('Y-m'));

        $qb = $em->getRepository(FormationSession::class)->createQueryBuilder('s')
            ->join('s.formation', 'f');

        if ($search) {
            $qb->andWhere('f.title LIKE :search OR s.trainer LIKE :search OR s.location LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        if ($status) {
            $qb->andWhere('s.status = :status')->setParameter('status', $status);
        }

        $sessions = $qb->getQuery()->getResult();

        return $this->render('backoffice/planning/index.html.twig', [
            'sessions' => $sessions,
            'search' => $search,
            'selectedStatus' => $status,
            'currentMonth' => $month,
        ]);
    }

    #[Route('/planning/new', name: 'app_backoffice_planning_new')]
    public function planningNew(Request $request, EntityManagerInterface $em): Response
    {
        $session = new FormationSession();
        $form = $this->createForm(FormationSessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $session->setCurrentParticipants(0);
            $session->setStatus('planifie');
            $em->persist($session);
            $em->flush();
            $this->addFlash('success', 'Session de formation ajoutée avec succès.');
            return $this->redirectToRoute('app_backoffice_planning');
        }

        return $this->render('backoffice/planning/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/planning/{id}/edit', name: 'app_backoffice_planning_edit')]
    public function planningEdit(FormationSession $session, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(FormationSessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Session modifiée avec succès.');
            return $this->redirectToRoute('app_backoffice_planning');
        }

        return $this->render('backoffice/planning/edit.html.twig', [
            'form' => $form->createView(),
            'session' => $session,
        ]);
    }

    #[Route('/planning/{id}/delete', name: 'app_backoffice_planning_delete')]
    public function planningDelete(FormationSession $session, EntityManagerInterface $em): Response
    {
        $em->remove($session);
        $em->flush();
        $this->addFlash('success', 'Session supprimée avec succès.');
        return $this->redirectToRoute('app_backoffice_planning');
    }

    // ========== GESTION DES INSCRIPTIONS ==========

    #[Route('/inscriptions', name: 'app_backoffice_inscriptions')]
    public function inscriptionsList(EntityManagerInterface $em, Request $request): Response
    {
        $status = $request->query->get('status', 'EN_ATTENTE');

        $inscriptions = $em->getRepository(Inscription::class)
            ->createQueryBuilder('i')
            ->leftJoin('i.user', 'u')
            ->leftJoin('i.formation', 'f')
            ->addSelect('u', 'f')
            ->where('i.status = :status')
            ->setParameter('status', $status)
            ->orderBy('i.dateDemande', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('backoffice/inscription/index.html.twig', [
            'inscriptions' => $inscriptions,
            'currentStatus' => $status,
        ]);
    }

    #[Route('/inscription/{id}/accepter', name: 'app_backoffice_inscription_accepter')]
    public function inscriptionAccepter(
        Inscription $inscription,
        Request $request,
        EntityManagerInterface $em,
        AIQuizGenerator $quizGenerator
    ): Response {
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('accepter'.$inscription->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_backoffice_inscriptions', ['status' => 'EN_ATTENTE']);
        }

        $user = $inscription->getUser();
        if (!$user) {
            $this->addFlash('error', 'Aucun utilisateur associé à cette inscription.');
            return $this->redirectToRoute('app_backoffice_inscriptions', ['status' => 'EN_ATTENTE']);
        }

        $formation = $inscription->getFormation();
        if (!$formation) {
            $this->addFlash('error', 'Aucune formation associée à cette inscription.');
            return $this->redirectToRoute('app_backoffice_inscriptions', ['status' => 'EN_ATTENTE']);
        }

        $inscription->setStatus('ACCEPTEE');
        $inscription->setDateReponse(new \DateTime());

        $quizExistant = $em->getRepository(Quiz_result::class)
            ->findOneBy(['user' => $user, 'training' => $formation]);

        // DEBUG
        file_put_contents('groq_debug.txt',
            date('H:i:s') . " - quizExistant: " . ($quizExistant ? 'OUI id='.$quizExistant->getId() : 'NON') . "\n",
            FILE_APPEND
        );

        if (!$quizExistant) {
            file_put_contents('groq_debug.txt',
                date('H:i:s') . " - AVANT generateQuiz formation: " . ($formation->getTitle() ?? '') . "\n",
                FILE_APPEND
            );

            try {
                $questions = $quizGenerator->generateQuiz(
                    $formation->getTitle() ?? '', // garantit une chaîne non nulle
                    $formation->getDescription() ?? ''
                );

                $nbQuestions = count($questions);
                // Évite l'accès à un offset inexistant
                $q1 = '';
                if ($nbQuestions > 0 && isset($questions[0]['question'])) {
                    $q1 = $questions[0]['question'];
                } else {
                    $q1 = '?';
                }

                file_put_contents('groq_debug.txt',
                    date('H:i:s') . " - APRES generateQuiz - nb questions: " . $nbQuestions . " - q1: " . $q1 . "\n",
                    FILE_APPEND
                );

                $quizResult = new Quiz_result();
                $quizResult->setUser($user);
                $quizResult->setTraining($formation);
                $quizResult->setScore(null);
                $quizResult->setTotalQuestions($nbQuestions);
                $quizResult->setPercentage(null);
                $quizResult->setPassed(null);
                $quizResult->setCompletedAt(null);

                // Transforme le tableau de questions en un tableau de chaînes JSON
                $encodedQuestions = array_map(fn(array $q): string => json_encode($q), $questions);
                $quizResult->setQuestions($encodedQuestions);

                $em->persist($quizResult);

                $this->addFlash('success', sprintf(
                    'Inscription acceptée. Un quiz de %d questions a été généré.',
                    $nbQuestions
                ));
            } catch (\Exception $e) {
                file_put_contents('groq_debug.txt',
                    date('H:i:s') . " - EXCEPTION: " . $e->getMessage() . "\n",
                    FILE_APPEND
                );
                $this->addFlash('warning', 'Inscription acceptée, mais la génération du quiz a échoué : ' . $e->getMessage());
            }
        } else {
            file_put_contents('groq_debug.txt',
                date('H:i:s') . " - Quiz existant trouvé, pas de régénération\n",
                FILE_APPEND
            );
        }

        $em->flush();

        return $this->redirectToRoute('app_backoffice_inscriptions', ['status' => 'EN_ATTENTE']);
    }

    #[Route('/inscription/{id}/refuser', name: 'app_backoffice_inscription_refuser')]
    public function inscriptionRefuser(Inscription $inscription, Request $request, EntityManagerInterface $em): Response
    {
        $token = (string) $request->request->get('_token');
        if ($this->isCsrfTokenValid('refuser'.$inscription->getId(), $token)) {
            $commentaire = (string) $request->request->get('commentaire', '');

            $inscription->setStatus('REFUSEE');
            $inscription->setDateReponse(new \DateTime());
            $inscription->setCommentaireAdmin($commentaire);

            $em->flush();

            $this->addFlash('success', 'Inscription refusée.');
        }

        return $this->redirectToRoute('app_backoffice_inscriptions', ['status' => 'EN_ATTENTE']);
    }
}