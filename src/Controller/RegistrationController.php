<?php

namespace App\Controller;

use App\Entity\UserAccount;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): Response {
        // Log 1: Début du processus
        $logger->info('=== DÉBUT INSCRIPTION ===');
        
        if ($this->getUser()) {
            $logger->info('Utilisateur déjà connecté, redirection');
            return $this->redirectToRoute('app_dashboard');
        }

        $user = new UserAccount();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        // Log 2: Vérification de la soumission
        $logger->info('Méthode HTTP: ' . $request->getMethod());
        $logger->info('Formulaire soumis: ' . ($form->isSubmitted() ? 'OUI' : 'NON'));
        
        if ($form->isSubmitted()) {
            $logger->info('=== FORMULAIRE SOUMIS ===');
            $logger->info('Données POST: ' . json_encode($request->request->all()));
            
            // Vérifier les erreurs du formulaire
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            
            if (count($errors) > 0) {
                $logger->error('Erreurs formulaire: ' . implode(', ', $errors));
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }
            
            $logger->info('Formulaire valide: ' . ($form->isValid() ? 'OUI' : 'NON'));
            
            if ($form->isValid()) {
                try {
                    $logger->info('=== SAUVEGARDE UTILISATEUR ===');
                    
                    $plainPassword = $form->get('plainPassword')->getData();
                    $logger->info('Mot de passe reçu: ' . ($plainPassword ? 'OUI (longueur: ' . strlen($plainPassword) . ')' : 'NON'));
                    
                    $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                    $logger->info('Mot de passe hashé: ' . $hashedPassword);
                    
                    $user->setPasswordHash($hashedPassword);
                    
                    if (!$user->getRole()) {
                        $user->setRole('EMPLOYEE');
                        $logger->info('Rôle défini: EMPLOYEE');
                    }
                    
                    $logger->info('Email: ' . $user->getEmail());
                    $logger->info('Username: ' . $user->getUsername());
                    
                    $entityManager->persist($user);
                    $logger->info('EntityManager: persist appelé');
                    
                    $entityManager->flush();
                    $logger->info('EntityManager: flush exécuté avec succès');
                    
                    $this->addFlash('success', 'Inscription réussie ! Veuillez vous connecter.');
                    $logger->info('=== INSCRIPTION RÉUSSIE ===');
                    
                    return $this->redirectToRoute('app_login');
                    
                } catch (\Exception $e) {
                    $logger->error('EXCEPTION: ' . $e->getMessage());
                    $logger->error('Trace: ' . $e->getTraceAsString());
                    $this->addFlash('error', 'Erreur technique: ' . $e->getMessage());
                }
            } else {
                $logger->warning('Formulaire invalide, aucune sauvegarde effectuée');
            }
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}