<?php

declare(strict_types=1);

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
        /** @var \App\Entity\UserAccount|null $currentUser */
        $currentUser = $this->getUser();
        if ($currentUser instanceof UserAccount) {
            return $this->redirectToRoute('app_profile');
        }

        $user = new UserAccount();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string|null $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            if (!\is_string($plainPassword)) {
                throw new \LogicException('Le mot de passe doit être une chaîne de caractères.');
            }

            $user->setPasswordHash($passwordHasher->hashPassword($user, $plainPassword));
            $user->setAccountStatus('ACTIVE');
            $user->setIsActive(true);

            try {
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Inscription réussie ! Vous êtes maintenant connecté.');

                $authResponse = $userAuthenticator->authenticateUser(
                    $user,
                    $loginFormAuthenticator,
                    $request
                );

                // authenticateUser() peut retourner null, on garantit une Response
                if ($authResponse === null) {
                    return $this->redirectToRoute('app_profile');
                }

                return $authResponse;
            } catch (UniqueConstraintViolationException) {
                $this->addFlash('error', 'Un compte avec cet email ou ce nom d\'utilisateur existe déjà.');
            }
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}