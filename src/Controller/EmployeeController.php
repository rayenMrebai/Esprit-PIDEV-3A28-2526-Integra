<?php

namespace App\Controller;

use App\Entity\UserAccount;
use App\Form\ProfileFormType;
use App\Repository\SalaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employee')]
#[IsGranted('ROLE_USER')]
class EmployeeController extends AbstractController
{
    #[Route('/dashboard', name: 'app_employee_dashboard')]
    public function dashboard(): Response
    {
        /** @var UserAccount $user */
        $user = $this->getUser();
        
        return $this->render('frontoffice/employee/dashboard.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/mes-salaires', name: 'app_employee_salaires')]
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
        
        return $this->render('frontoffice/employee/mes_salaires.html.twig', [
            'user' => $user,
            'salaires' => $salaires,
            'totalPercu' => $totalPercu,
            'totalBonus' => $totalBonus,
            'nbPayes' => $nbPayes,
            'moyenne' => $moyenne,
            'nbTotal' => count($salaires)
        ]);
    }

    #[Route('/profile', name: 'app_employee_profile')]
    public function profile(): Response
    {
        /** @var UserAccount $user */
        $user = $this->getUser();
        
        return $this->render('frontoffice/employee/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/edit', name: 'app_employee_profile_edit')]
    public function edit(Request $request, EntityManagerInterface $em): Response
    {
        /** @var UserAccount $user */
        $user = $this->getUser();
        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Profil mis à jour.');
            return $this->redirectToRoute('app_employee_profile');
        }

        return $this->render('frontoffice/employee/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}