<?php

namespace App\Controller;

use App\Entity\UserAccount;
use App\Form\ProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'app_profile')]
    public function show(): Response
    {
        /** @var UserAccount $user */
        $user = $this->getUser();
        $roles = $user->getRoles();

        // Admin/Manager → template authenticated, Employé → template front
        $isEmployee = !in_array('ROLE_ADMIN', $roles) && !in_array('ROLE_MANAGER', $roles);
        $template = $isEmployee ? 'front/profile.html.twig' : 'profile/show.html.twig';

        return $this->render($template, ['user' => $user]);
    }

    #[Route('/edit', name: 'app_profile_edit')]
    public function edit(Request $request, EntityManagerInterface $em): Response
    {
        /** @var UserAccount $user */
        $user = $this->getUser();
        $roles = $user->getRoles();

        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Profil mis à jour.');
            return $this->redirectToRoute('app_profile');
        }

        $isEmployee = !in_array('ROLE_ADMIN', $roles) && !in_array('ROLE_MANAGER', $roles);
        $template = $isEmployee ? 'front/edit.html.twig' : 'profile/edit.html.twig';

        return $this->render($template, [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}