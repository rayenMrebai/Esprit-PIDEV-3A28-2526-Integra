<?php

namespace App\Controller;

use App\Entity\Training_program;
use App\Form\TrainingProgramType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/training-program')]
class TrainingProgramController extends AbstractController
{
    #[Route('/', name: 'app_training_program_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('backoffice/training_program/index.html.twig', [
            'training_programs' => $entityManager->getRepository(Training_program::class)->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_training_program_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $trainingProgram = new Training_program();
        $form = $this->createForm(TrainingProgramType::class, $trainingProgram);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($trainingProgram);
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true, 
                    'message' => 'Programme créé avec succès',
                    'id' => $trainingProgram->getId()
                ]);
            }
            return $this->redirectToRoute('app_training_program_index', [], Response::HTTP_SEE_OTHER);
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
            return $this->render('backoffice/training_program/_form.html.twig', [
                'training_program' => $trainingProgram,
                'form' => $form->createView(),
            ]);
        }

        return $this->render('backoffice/training_program/new.html.twig', [
            'training_program' => $trainingProgram,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_training_program_show', methods: ['GET'])]
    public function show(Training_program $trainingProgram): Response
    {
        return $this->render('backoffice/training_program/show.html.twig', [
            'training_program' => $trainingProgram,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_training_program_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Training_program $trainingProgram, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TrainingProgramType::class, $trainingProgram);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true, 
                    'message' => 'Programme modifié avec succès'
                ]);
            }
            return $this->redirectToRoute('app_training_program_index', [], Response::HTTP_SEE_OTHER);
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
            return $this->render('backoffice/training_program/_form.html.twig', [
                'training_program' => $trainingProgram,
                'form' => $form->createView(),
            ]);
        }

        return $this->render('backoffice/training_program/edit.html.twig', [
            'training_program' => $trainingProgram,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_training_program_delete', methods: ['POST'])]
    public function delete(Request $request, Training_program $trainingProgram, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$trainingProgram->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($trainingProgram);
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true, 
                    'message' => 'Programme supprimé avec succès'
                ]);
            }
        }

        return $this->redirectToRoute('app_training_program_index', [], Response::HTTP_SEE_OTHER);
    }
}