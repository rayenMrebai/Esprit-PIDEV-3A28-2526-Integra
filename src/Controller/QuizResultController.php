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
    #[Route('/', name: 'app_quiz_result_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('quiz_result/index.html.twig', [
            'quiz_results' => $entityManager->getRepository(Quiz_result::class)->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_quiz_result_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $quizResult = new Quiz_result();
        $form = $this->createForm(QuizResultType::class, $quizResult);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($quizResult);
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode(['success' => true, 'message' => 'Quiz ajouté avec succès']), 200, ['Content-Type' => 'application/json']);
            }
            return $this->redirectToRoute('app_quiz_result_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('quiz_result/_form.html.twig', [
                'quiz_result' => $quizResult,
                'form' => $form,
            ]);
        }

        return $this->render('quiz_result/new.html.twig', [
            'quiz_result' => $quizResult,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_quiz_result_show', methods: ['GET'])]
    public function show(Quiz_result $quizResult): Response
    {
        return $this->render('quiz_result/show.html.twig', [
            'quiz_result' => $quizResult,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_quiz_result_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Quiz_result $quizResult, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(QuizResultType::class, $quizResult);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode(['success' => true, 'message' => 'Quiz modifié avec succès']), 200, ['Content-Type' => 'application/json']);
            }
            return $this->redirectToRoute('app_quiz_result_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('quiz_result/_form.html.twig', [
                'quiz_result' => $quizResult,
                'form' => $form,
            ]);
        }

        return $this->render('quiz_result/edit.html.twig', [
            'quiz_result' => $quizResult,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_quiz_result_delete', methods: ['POST'])]
    public function delete(Request $request, Quiz_result $quizResult, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$quizResult->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($quizResult);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_quiz_result_index', [], Response::HTTP_SEE_OTHER);
    }
}