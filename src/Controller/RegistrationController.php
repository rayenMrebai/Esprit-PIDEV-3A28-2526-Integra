<?php

namespace App\Controller;

use App\Entity\UserAccount;
use App\Form\RegistrationFormType;
use App\Security\LoginFormAuthenticator;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        UserAuthenticatorInterface $userAuthenticator,
        LoginFormAuthenticator $loginFormAuthenticator
    ): Response {
        // Already logged in → redirect away
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }

        $user = new UserAccount();
        $form = $this->createForm(RegistrationFormType::class, $user);
        // is_admin defaults to false → no role field, role stays 'EMPLOYE'
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPasswordHash($passwordHasher->hashPassword($user, $plainPassword));
            $user->setAccountStatus('ACTIVE');
            $user->setIsActive(true);

            try {
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Inscription réussie ! Vous êtes maintenant connecté.');

                return $userAuthenticator->authenticateUser(
                    $user,
                    $loginFormAuthenticator,
                    $request
                );
            } catch (UniqueConstraintViolationException) {
                $this->addFlash('error', 'Un compte avec cet email ou ce nom d\'utilisateur existe déjà.');
            }
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}