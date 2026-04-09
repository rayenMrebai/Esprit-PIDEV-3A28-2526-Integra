<?php
// src/Controller/BackofficeController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/backoffice', name: 'app_backoffice_')]
class BackofficeController extends AbstractController
{
    // ── Vérifie que l'utilisateur est connecté ET est ADMINISTRATEUR ──
    private function requireAdmin(Request $request): ?Response
    {
        $role = $request->getSession()->get('fake_user_role');

        if (!$role) {
            // Pas connecté du tout
            return $this->redirectToRoute('fake_login');
        }

        if ($role !== 'ADMINISTRATEUR') {
            // Connecté mais pas admin → renvoyer sur le frontoffice
            return $this->redirectToRoute('app_dashboard');
        }

        return null;
    }

    #[Route('', name: 'dashboard')]
    public function dashboard(Request $request): Response
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        return $this->render('backoffice/index.html.twig');
    }

    #[Route('/utilisateurs', name: 'utilisateurs')]
    public function utilisateurs(Request $request): Response
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        return $this->render('backoffice/utilisateurs.html.twig');
    }

    #[Route('/formations', name: 'formations')]
    public function formations(Request $request): Response
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        return $this->render('backoffice/formations.html.twig');
    }

    #[Route('/projets', name: 'projets')]
    public function projets(Request $request): Response
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        return $this->render('backoffice/projets.html.twig');
    }

    #[Route('/recrutement', name: 'recrutement')]
    public function recrutement(Request $request): Response
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        return $this->render('backoffice/recrutement.html.twig');
    }
}