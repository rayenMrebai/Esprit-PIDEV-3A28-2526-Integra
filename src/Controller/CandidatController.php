<?php

namespace App\Controller;

use App\Entity\Candidat;
use App\Form\CandidatType;
use App\Repository\CandidatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\TelegramService;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/candidat')]
final class CandidatController extends AbstractController
{
    #[Route(name: 'app_candidat_index', methods: ['GET'])]
    public function index(CandidatRepository $candidatRepository): Response
    {
        return $this->render('candidat/index.html.twig', [
            'candidats' => $candidatRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_candidat_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $candidat = new Candidat();
        $form = $this->createForm(CandidatType::class, $candidat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($candidat);
            $entityManager->flush();

            return $this->redirectToRoute('app_candidat_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('candidat/new.html.twig', [
            'candidat' => $candidat,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_candidat_show', methods: ['GET'])]
    public function show(Candidat $candidat): Response
    {
        return $this->render('candidat/show.html.twig', [
            'candidat' => $candidat,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_candidat_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Candidat $candidat, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CandidatType::class, $candidat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_recruitment_dashboard');
        }

        return $this->render('candidat/edit.html.twig', [
            'candidat' => $candidat,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_candidat_delete', methods: ['POST'])]
    public function delete(Request $request, Candidat $candidat, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$candidat->getId(), $request->request->get('_token'))) {
            $entityManager->remove($candidat);
            $entityManager->flush();
            $this->addFlash('success', 'Candidat supprimé avec succès.');
        }
        return $this->redirectToRoute('app_recruitment_dashboard', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/reject', name: 'app_candidat_reject', methods: ['POST'])]
    public function reject(Request $request, Candidat $candidat, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('reject'.$candidat->getId(), $request->request->get('_token'))) {
            $candidat->setStatus('Rejeté');
            $entityManager->flush();
            $this->addFlash('success', 'Candidat rejeté avec succès.');
        }
        return $this->redirectToRoute('app_recruitment_dashboard');
    }

    #[Route('/{id}/telegram', name: 'app_candidat_telegram', methods: ['POST'])]
    public function sendTelegram(Candidat $candidat, TelegramService $telegram): JsonResponse
    {
        $chatId = $_ENV['TELEGRAM_CHAT_ID'] ?? null;
        if (!$chatId) {
            return $this->json(['error' => 'Chat ID non configuré'], 500);
        }

        $message = sprintf(
            "📢 *Nouvelle notification recrutement*\n\n" .
            "Candidat : %s %s\n" .
            "Email : %s\n" .
            "Téléphone : %s\n" .
            "Poste : %s\n" .
            "Statut : %s",
            $candidat->getFirstName(),
            $candidat->getLastName(),
            $candidat->getEmail(),
            $candidat->getPhone(),
            $candidat->getJobposition() ? $candidat->getJobposition()->getTitle() : 'Non spécifié',
            $candidat->getStatus()
        );

        $sent = $telegram->sendMessage($chatId, $message);
        if ($sent) {
            return $this->json(['success' => true]);
        }
        return $this->json(['error' => 'Échec de l\'envoi Telegram'], 500);
    }
}