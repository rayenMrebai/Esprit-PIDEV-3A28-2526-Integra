<?php

namespace App\Controller;

use App\Entity\Quiz_result;
use App\Form\QuizResultType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/quiz-result')]
class QuizResultController extends AbstractController
{
    #[Route('/new', name: 'app_quiz_result_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $quiz = new Quiz_result();
        $form = $this->createForm(QuizResultType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($quiz);
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Quiz créé avec succès',
                    'id' => $quiz->getId()
                ]);
            }
            return $this->redirectToRoute('app_backoffice_formations');
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('quiz_result/_form.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        return $this->render('quiz_result/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_quiz_result_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Quiz_result $quiz, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(QuizResultType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Quiz modifié avec succès'
                ]);
            }
            return $this->redirectToRoute('app_backoffice_formations');
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('quiz_result/_form.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        return $this->render('quiz_result/edit.html.twig', [
            'form' => $form->createView(),
            'quiz' => $quiz,
        ]);
    }

    #[Route('/{id}', name: 'app_quiz_result_delete', methods: ['POST'])]
    public function delete(Request $request, Quiz_result $quiz, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $quiz->getId(), $request->request->get('_token'))) {
            $entityManager->remove($quiz);
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Quiz supprimé avec succès'
                ]);
            }
        }

        return $this->redirectToRoute('app_backoffice_formations');
    }

    #[Route('/{id}', name: 'app_quiz_result_show', methods: ['GET'])]
    public function show(Quiz_result $quiz): Response
    {
        return $this->render('quiz_result/show.html.twig', [
            'quiz' => $quiz,
        ]);
    }
}