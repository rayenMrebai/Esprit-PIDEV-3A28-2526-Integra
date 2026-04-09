<?php

namespace App\Controller;

use App\Entity\Skill;
use App\Form\SkillType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/skill')]
class SkillController extends AbstractController
{
    #[Route('/', name: 'app_skill_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('backoffice/skill/index.html.twig', [
            'skills' => $entityManager->getRepository(Skill::class)->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_skill_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $skill = new Skill();
        $form = $this->createForm(SkillType::class, $skill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($skill);
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true, 
                    'message' => 'Compétence créée avec succès',
                    'id' => $skill->getId()
                ]);
            }
            return $this->redirectToRoute('app_skill_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($request->isXmlHttpRequest() && $form->isSubmitted()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            return $this->json([
                'success' => false, 
                'message' => 'Erreur de validation',
                'errors' => $errors
            ], 400);
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('backoffice/skill/_form.html.twig', [
                'skill' => $skill,
                'form' => $form->createView(),
            ]);
        }

        return $this->render('backoffice/skill/new.html.twig', [
            'skill' => $skill,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_skill_show', methods: ['GET'])]
    public function show(Skill $skill): Response
    {
        return $this->render('backoffice/skill/show.html.twig', [
            'skill' => $skill,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_skill_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Skill $skill, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SkillType::class, $skill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true, 
                    'message' => 'Compétence modifiée avec succès'
                ]);
            }
            return $this->redirectToRoute('app_skill_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($request->isXmlHttpRequest() && $form->isSubmitted()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            return $this->json([
                'success' => false, 
                'message' => 'Erreur de validation',
                'errors' => $errors
            ], 400);
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('backoffice/skill/_form.html.twig', [
                'skill' => $skill,
                'form' => $form->createView(),
            ]);
        }

        return $this->render('backoffice/skill/edit.html.twig', [
            'skill' => $skill,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_skill_delete', methods: ['POST'])]
    public function delete(Request $request, Skill $skill, EntityManagerInterface $entityManager): Response
    {
        $token = $request->request->get('_token');
        
        if ($this->isCsrfTokenValid('delete' . $skill->getId(), $token)) {
            $entityManager->remove($skill);
            $entityManager->flush();
            
            $this->addFlash('success', 'Compétence supprimée avec succès');
        } else {
            $this->addFlash('error', 'Token CSRF invalide');
        }
        
        return $this->redirectToRoute('app_backoffice_formations');
    }
}