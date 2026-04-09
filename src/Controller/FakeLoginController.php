<?php
// src/Controller/FakeLoginController.php

namespace App\Controller;

use App\Repository\UserAccountRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FakeLoginController extends AbstractController
{
    /**
     * Affiche le formulaire de sélection utilisateur (simulation login).
     */
    #[Route('/fake-login', name: 'fake_login', methods: ['GET'])]
    public function index(UserAccountRepository $repo): Response
    {
        // Ne liste que les comptes actifs
        $users = $repo->findBy(['isActive' => true]);

        return $this->render('login_fake.html.twig', [
            'users' => $users,
        ]);
    }

    /**
     * Traite la sélection et enregistre l'utilisateur en session.
     */
    #[Route('/fake-login', name: 'fake_login_post', methods: ['POST'])]
    public function login(Request $request, UserAccountRepository $repo): Response
    {
        $userId = (int) $request->request->get('userId');

        if (!$userId) {
            $this->addFlash('error', 'Veuillez sélectionner un utilisateur.');
            return $this->redirectToRoute('fake_login');
        }

        $user = $repo->find($userId);

        if (!$user || !$user->isActive()) {
            $this->addFlash('error', 'Utilisateur introuvable ou inactif.');
            return $this->redirectToRoute('fake_login');
        }

        // ── Stockage en session ──────────────────────────────────────────
        $session = $request->getSession();
        $session->set('fake_user_id',    $user->getUserId());
        $session->set('fake_user_name',  $user->getUsername());
        $session->set('fake_user_email', $user->getEmail());
        $session->set('fake_user_role',  $user->getRole());
        // ────────────────────────────────────────────────────────────────

        // ── Redirection selon le rôle ────────────────────────────────────
        return match ($user->getRole()) {
            'ADMINISTRATEUR' => $this->redirectToRoute('app_backoffice_dashboard'),
            'MANAGER'        => $this->redirectToRoute('app_dashboard'),
            'EMPLOYE'        => $this->redirectToRoute('app_dashboard'),
            default          => $this->redirectToRoute('app_dashboard'),
        };
    }

    /**
     * Déconnexion : vide la session et redirige vers le fake login.
     */
    #[Route('/fake-logout', name: 'fake_logout', methods: ['GET'])]
    public function logout(Request $request): Response
    {
        $request->getSession()->invalidate();

        return $this->redirectToRoute('fake_login');
    }
}
