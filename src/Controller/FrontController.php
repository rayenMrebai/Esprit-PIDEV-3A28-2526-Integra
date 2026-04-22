<?php

namespace App\Controller;

use App\Entity\UserAccount;
use App\Repository\JobpositionRepository;
use App\Repository\SalaireRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ProfileFormType;

class FrontController extends AbstractController
{
    #[Route('/', name: 'front_acceuil')]
    public function acceuil(JobpositionRepository $jobpositionRepository): Response
    {
        $jobs = $jobpositionRepository->findBy([], ['postedAt' => 'DESC']);

        return $this->render('front/acceuil.html.twig', [
            'jobs' => $jobs,
        ]);
    }
    #[Route('/home', name: 'front_home')]
    public function home(JobpositionRepository $jobpositionRepository): Response
    {
        $jobs = $jobpositionRepository->findBy([], ['postedAt' => 'DESC']);

        return $this->render('front/home.html.twig', [
            'jobs' => $jobs,
        ]);
    }

    #[Route('/offre/{id}', name: 'front_job_show')]
    public function show(JobpositionRepository $jobpositionRepository, int $id): Response
    {
        $job = $jobpositionRepository->find($id);
        if (!$job) {
            throw $this->createNotFoundException('Offre non trouvée');
        }
        return $this->render('front/job_show.html.twig', [
            'job' => $job,
        ]);
    }
    



    // ─────────────────────────────────────────────────────────────
    // 4. Mes Salaires (ex-employee/mes-salaires)
    // ─────────────────────────────────────────────────────────────
    #[Route('/front/mes-salaires', name: 'app_employee_salaires')]
    #[IsGranted('ROLE_USER')]
    public function mesSalaires(SalaireRepository $salaireRepo): Response
    {
        /** @var UserAccount $user */
        $user = $this->getUser();
        
        // Récupérer les salaires de l'employé connecté
        $salaires = $salaireRepo->findBy(
            ['user' => $user], 
            ['datePaiement' => 'DESC']
        );
        
        // Calculer les statistiques
        $totalPercu = 0;
        $totalBonus = 0;
        $nbPayes = 0;
        
        foreach ($salaires as $salaire) {
            $totalPercu += $salaire->getTotalAmount();
            $totalBonus += $salaire->getBonusAmount();
            if ($salaire->getStatus() === 'PAYÉ') {
                $nbPayes++;
            }
        }
        
        $moyenne = count($salaires) > 0 ? $totalPercu / count($salaires) : 0;
        
        return $this->render('front/mes_salaires.html.twig', [
            'user' => $user,
            'salaires' => $salaires,
            'totalPercu' => $totalPercu,
            'totalBonus' => $totalBonus,
            'nbPayes' => $nbPayes,
            'moyenne' => $moyenne,
            'nbTotal' => count($salaires)
        ]);
    }
}