<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FakeLoginController extends AbstractController
{
    #[Route('/fake-login', name: 'fake_login', methods: ['GET'])]
    public function index(UserRepository $repo): Response
    {
        $users = $repo->findBy(['isActive' => true]);

        return $this->render('login_fake.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/fake-login', name: 'fake_login_post', methods: ['POST'])]
    public function login(Request $request, UserRepository $repo): Response
    {
        $userId = (int) $request->request->get('userId');

        if (!$userId) {
            $this->addFlash('error', 'Veuillez sélectionner un utilisateur.');
            return $this->redirectToRoute('fake_login');
        }

        $user = $repo->find($userId);

        if (!$user || !$user->getIsActive()) {
            $this->addFlash('error', 'Utilisateur introuvable ou inactif.');
            return $this->redirectToRoute('fake_login');
        }

        $session = $request->getSession();
        $session->set('fake_user_id',    $user->getUserid());
        $session->set('fake_user_name',  $user->getUsername());
        $session->set('fake_user_email', $user->getEmail());
        $session->set('fake_user_role',  $user->getRole());

        $role = $user->getRole();
        
        if ($role === 'ADMINISTRATEUR') {
            return $this->redirectToRoute('app_backoffice_dashboard');
        } else {
            return $this->redirectToRoute('app_frontoffice_index');
        }
    }

    #[Route('/fake-logout', name: 'fake_logout', methods: ['GET'])]
    public function logout(Request $request): Response
    {
        $request->getSession()->invalidate();
        return $this->redirectToRoute('fake_login');
    }
}