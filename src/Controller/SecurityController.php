<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si déjà connecté, rediriger selon le rôle
        if ($this->getUser()) {
            return $this->redirectToDashboard();
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    private function redirectToDashboard(): Response
    {
        // Si Admin ou Manager → Dashboard AdminLTE
        if ($this->isGranted('ROLE_MANAGER')) {
            return $this->redirectToRoute('app_dashboard');
        }
        
        // Si Employé simple → Dashboard Clean Blog
        return $this->redirectToRoute('app_employee_dashboard');
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}