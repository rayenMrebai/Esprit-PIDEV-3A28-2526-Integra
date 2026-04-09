<?php
// src/Controller/FrontofficeController.php

namespace App\Controller;

use App\Repository\SalaireRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FrontofficeController extends AbstractController
{
    // ─── Garde session : redirige vers login si pas connecté ────────────
    private function requireSession(Request $request): ?Response
    {
        if (!$request->getSession()->get('fake_user_id')) {
            return $this->redirectToRoute('fake_login');
        }
        return null;
    }

    // ─── Dashboard ──────────────────────────────────────────────────────
    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function dashboard(Request $request): Response
    {
        if ($redirect = $this->requireSession($request)) return $redirect;

        return $this->render('frontoffice/dashboard.html.twig');
    }

    // ─── Salaires ───────────────────────────────────────────────────────
    #[Route('/salaires', name: 'app_salaires', methods: ['GET'])]
    public function salaires(Request $request, SalaireRepository $repo): Response
    {
        if ($redirect = $this->requireSession($request)) return $redirect;

        $userId   = $request->getSession()->get('fake_user_id');
        $salaires = $repo->findBy(
            ['user' => $userId],
            ['datePaiement' => 'DESC']
        );

        return $this->render('frontoffice/salaires/index.html.twig', [
            'salaires' => $salaires,
        ]);
    }

    // ─── Formations (placeholder) ────────────────────────────────────────
    #[Route('/formations', name: 'app_formations', methods: ['GET'])]
    public function formations(Request $request): Response
    {
        if ($redirect = $this->requireSession($request)) return $redirect;

        return $this->render('frontoffice/formations/index.html.twig');
    }

    // ─── Projets (placeholder) ───────────────────────────────────────────
    #[Route('/projets', name: 'app_projets', methods: ['GET'])]
    public function projets(Request $request): Response
    {
        if ($redirect = $this->requireSession($request)) return $redirect;

        return $this->render('frontoffice/projets/index.html.twig');
    }

    // ─── Recrutement (placeholder) ───────────────────────────────────────
    #[Route('/recrutements', name: 'app_recrutements', methods: ['GET'])]
    public function recrutements(Request $request): Response
    {
        if ($redirect = $this->requireSession($request)) return $redirect;

        return $this->render('frontoffice/recrutements/index.html.twig');
    }

    // ─── Profil (placeholder) ────────────────────────────────────────────
    #[Route('/profile', name: 'app_profile', methods: ['GET'])]
    public function profile(Request $request): Response
    {
        if ($redirect = $this->requireSession($request)) return $redirect;

        return $this->render('frontoffice/profile/index.html.twig');
    }
}
