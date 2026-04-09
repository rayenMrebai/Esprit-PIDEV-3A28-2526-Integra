<?php

namespace App\Controller;

use App\Entity\UserAccount;
use App\Form\UserAccountType;
use App\Repository\UserAccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user/account')]
final class UserAccountController extends AbstractController
{
    #[Route(name: 'app_user_account_index', methods: ['GET'])]
    public function index(UserAccountRepository $userAccountRepository): Response
    {
        return $this->render('user_account/index.html.twig', [
            'user_accounts' => $userAccountRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_account_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $userAccount = new UserAccount();
        $form = $this->createForm(UserAccountType::class, $userAccount);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($userAccount);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_account_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user_account/new.html.twig', [
            'user_account' => $userAccount,
            'form' => $form,
        ]);
    }

    #[Route('/{userId}', name: 'app_user_account_show', methods: ['GET'])]
    public function show(UserAccount $userAccount): Response
    {
        return $this->render('user_account/show.html.twig', [
            'user_account' => $userAccount,
        ]);
    }

    #[Route('/{userId}/edit', name: 'app_user_account_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UserAccount $userAccount, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserAccountType::class, $userAccount);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_user_account_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user_account/edit.html.twig', [
            'user_account' => $userAccount,
            'form' => $form,
        ]);
    }

    #[Route('/{userId}', name: 'app_user_account_delete', methods: ['POST'])]
    public function delete(Request $request, UserAccount $userAccount, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$userAccount->getUserId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($userAccount);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_account_index', [], Response::HTTP_SEE_OTHER);
    }
}
