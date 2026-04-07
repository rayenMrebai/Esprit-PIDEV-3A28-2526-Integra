<?php

namespace App\Controller;

use App\Entity\UserAccount;
use App\Repository\UserAccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user-account')]
class UserAccountController extends AbstractController
{
    #[Route('/', name: 'user_account_index', methods: ['GET'])]
    public function index(UserAccountRepository $repo): Response
    {
        return $this->render('user_account/index.html.twig', [
            'users' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'user_account_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $user = new UserAccount();
            $user->setUsername($request->request->get('username'));
            $user->setEmail($request->request->get('email'));
            $user->setPasswordHash(password_hash($request->request->get('password'), PASSWORD_BCRYPT));
            $user->setRole($request->request->get('role', 'EMPLOYE'));
            $user->setIsActive(true);
            $user->setAccountStatus('ACTIVE');

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès');
            return $this->redirectToRoute('user_account_index');
        }

        return $this->render('user_account/new.html.twig');
    }

    #[Route('/{userId}', name: 'user_account_show', methods: ['GET'])]
    public function show(UserAccount $user): Response
    {
        return $this->render('user_account/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{userId}/edit', name: 'user_account_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UserAccount $user, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $user->setUsername($request->request->get('username'));
            $user->setEmail($request->request->get('email'));
            $user->setRole($request->request->get('role'));
            $user->setAccountStatus($request->request->get('accountStatus'));
            $user->setIsActive($request->request->get('isActive') === '1');

            $em->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès');
            return $this->redirectToRoute('user_account_index');
        }

        return $this->render('user_account/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{userId}/delete', name: 'user_account_delete', methods: ['POST'])]
    public function delete(UserAccount $user, EntityManagerInterface $em): Response
    {
        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Utilisateur supprimé');
        return $this->redirectToRoute('user_account_index');
    }
}