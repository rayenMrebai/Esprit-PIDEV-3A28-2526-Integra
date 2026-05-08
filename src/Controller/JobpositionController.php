<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Jobposition;
use App\Form\JobpositionType;
use App\Repository\JobpositionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/jobposition')]
final class JobpositionController extends AbstractController
{
    #[Route(name: 'app_jobposition_index', methods: ['GET'])]
    public function index(JobpositionRepository $jobpositionRepository): Response
    {
        return $this->render('jobposition/index.html.twig', [
            'jobpositions' => $jobpositionRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_jobposition_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $jobposition = new Jobposition();
        $form = $this->createForm(JobpositionType::class, $jobposition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($jobposition);
            $entityManager->flush();

            return $this->redirectToRoute('app_jobposition_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('jobposition/new.html.twig', [
            'jobposition' => $jobposition,
            'form' => $form,
        ]);
    }

    #[Route('/{idJob}', name: 'app_jobposition_show', methods: ['GET'])]
    public function show(Jobposition $jobposition): Response
    {
        return $this->render('jobposition/show.html.twig', [
            'jobposition' => $jobposition,
        ]);
    }

    #[Route('/{idJob}/edit', name: 'app_jobposition_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Jobposition $jobposition, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(JobpositionType::class, $jobposition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_recruitment_dashboard');
        }

        return $this->render('jobposition/edit.html.twig', [
            'jobposition' => $jobposition,
            'form' => $form,
        ]);
    }

    #[Route('/{idJob}', name: 'app_jobposition_delete', methods: ['POST'])]
    public function delete(Request $request, Jobposition $jobposition, EntityManagerInterface $entityManager): Response
    {
        $token = (string) $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete'.$jobposition->getIdJob(), $token)) {
            $entityManager->remove($jobposition);
            $entityManager->flush();
            $this->addFlash('success', 'Offre supprimée avec succès.');
        }
        return $this->redirectToRoute('app_recruitment_dashboard', [], Response::HTTP_SEE_OTHER);
    }
}