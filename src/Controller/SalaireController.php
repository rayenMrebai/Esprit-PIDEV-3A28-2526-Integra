<?php

namespace App\Controller;

use App\Entity\Salaire;
use App\Form\SalaireType;
use App\Form\SalaireEditType;
use App\Repository\SalaireRepository;
use App\Repository\UserAccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/salaire')]
class SalaireController extends AbstractController
{
    #[Route('/', name: 'salaire_index', methods: ['GET'])]
    public function index(SalaireRepository $repo): Response
    {
        return $this->render('salaire/index.html.twig', [
            'salaires' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'salaire_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserAccountRepository $userRepo): Response
    {
        $salaire = new Salaire();
        $form = $this->createForm(SalaireType::class, $salaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Validation supplémentaire : date doit être >= aujourd'hui
            $datePaiement = $salaire->getDatePaiement();
            $aujourdhui = new \DateTime('today');
            
            if ($datePaiement < $aujourdhui) {
                $this->addFlash('error', 'La date de paiement ne peut pas être dans le passé.');
                return $this->render('backoffice/salaires/salaire/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
            
            // Calcul du total
            $salaire->setTotalAmount($salaire->getBaseAmount() + $salaire->getBonusAmount());
            $salaire->setStatus('CREÉ');
            
            $em->persist($salaire);
            $em->flush();

            $this->addFlash('success', 'Salaire créé avec succès');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        return $this->render('backoffice/salaires/salaire/new.html.twig', [
            'form' => $form->createView(),
        ]);
}

    #[Route('/{id}', name: 'salaire_show', methods: ['GET'])]
    public function show(Salaire $salaire): Response
    {
        return $this->render('salaire/show.html.twig', [
            'salaire' => $salaire,
        ]);
    }

    #[Route('/{id}/edit', name: 'salaire_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Salaire $salaire, EntityManagerInterface $em): Response
    {
        // Bloquer l'accès edit si salaire déjà PAYÉ
        if ($salaire->getStatus() === 'PAYÉ') {
            $this->addFlash('error', 'Impossible de modifier un salaire déjà payé.');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        $form = $this->createForm(SalaireEditType::class, $salaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Validation spécifique : si statut PAYÉ, date doit être aujourd'hui
            if ($salaire->getStatus() === 'PAYÉ') {
                $today = new \DateTime('today');
                if ($salaire->getDatePaiement()->format('Y-m-d') !== $today->format('Y-m-d')) {
                    $this->addFlash('error', "Quand le statut est 'PAYÉ', la date de paiement doit être aujourd'hui.");
                    return $this->render('backoffice/salaires/salaire/edit.html.twig', [
                        'form' => $form->createView(),
                        'salaire' => $salaire,
                    ]);
                }
            }

            $salaire->setUpdatedAt(new \DateTime());
            $em->flush();

            $this->addFlash('success', 'Salaire modifié avec succès');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        return $this->render('backoffice/salaires/salaire/edit.html.twig', [
            'form' => $form->createView(),
            'salaire' => $salaire,
        ]);
    }

    #[Route('/{id}/delete', name: 'salaire_delete', methods: ['POST'])]
    public function delete(Request $request, Salaire $salaire, EntityManagerInterface $em): Response
    {
        // Bloquer suppression si PAYÉ
        if ($salaire->getStatus() === 'PAYÉ') {
            $this->addFlash('error', 'Impossible de supprimer un salaire déjà payé.');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        if ($this->isCsrfTokenValid('delete' . $salaire->getId(), $request->request->get('_token'))) {
            $em->remove($salaire);
            $em->flush();
            $this->addFlash('success', 'Salaire supprimé');
        }

        return $this->redirectToRoute('app_backoffice_salaires_index');
    }
}