<?php

namespace App\Controller;

use App\Entity\Salaire;
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
        $errors = [];

        if ($request->isMethod('POST')) {
            $userId       = $request->request->get('userId');
            $baseAmount   = $request->request->get('baseAmount');
            $datePaiement = $request->request->get('datePaiement');

            // Vérification employé
            if (empty($userId)) {
                $errors[] = "L'employé est obligatoire.";
            }

            // Vérification salaire de base
            if (empty($baseAmount)) {
                $errors[] = "Le salaire de base est obligatoire.";
            } elseif (!is_numeric($baseAmount)) {
                $errors[] = "Le salaire de base doit être un nombre.";
            } elseif ((float)$baseAmount <= 0) {
                $errors[] = "Le salaire de base doit être supérieur à 0.";
            }

            // Vérification date
            if (empty($datePaiement)) {
                $errors[] = "La date de paiement est obligatoire.";
            } else {
                $dateChoisie = new \DateTime($datePaiement);
                $aujourdhui  = new \DateTime('today');
                if ($dateChoisie < $aujourdhui) {
                    $errors[] = "La date de paiement ne peut pas être dans le passé.";
                }
            }

            // Vérification employé existe
            $user = !empty($userId) ? $userRepo->find($userId) : null;
            if (!$user) {
                $errors[] = "L'employé sélectionné n'existe pas.";
            }

            if (empty($errors)) {
                $salaire = new Salaire();
                $salaire->setUser($user);
                $salaire->setBaseAmount((float)$baseAmount);
                $salaire->setBonusAmount(0);
                $salaire->setTotalAmount((float)$baseAmount);
                $salaire->setStatus('CREÉ');
                $salaire->setDatePaiement(new \DateTime($datePaiement));

                $em->persist($salaire);
                $em->flush();

                $this->addFlash('success', 'Salaire créé avec succès');
                return $this->redirectToRoute('salaire_index');
            }
        }

        return $this->render('salaire/new.html.twig', [
            'users'  => $userRepo->findAll(),
            'errors' => $errors,
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
        // ⭐ Bloquer l'accès edit si salaire déjà PAYÉ
        if ($salaire->getStatus() === 'PAYÉ') {
            $this->addFlash('error', 'Impossible de modifier un salaire déjà payé.');
            return $this->redirectToRoute('salaire_index');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $status       = $request->request->get('status');
            $datePaiement = $request->request->get('datePaiement');

            // Vérification statut
            if (empty($status)) {
                $errors[] = "Le statut est obligatoire.";
            } elseif (!in_array($status, ['CREÉ', 'EN_COURS', 'PAYÉ'])) {
                $errors[] = "Le statut est invalide.";
            }

            // Vérification date
            if (empty($datePaiement)) {
                $errors[] = "La date de paiement est obligatoire.";
            } else {
                $dateChoisie = new \DateTime($datePaiement);
                $aujourdhui  = new \DateTime('today');

                // Date ne doit pas être dans le passé
                if ($dateChoisie < $aujourdhui) {
                    $errors[] = "La date de paiement ne peut pas être dans le passé.";
                }

                // Si statut PAYÉ → date doit être aujourd'hui
                if ($status === 'PAYÉ' && $dateChoisie != $aujourdhui) {
                    $errors[] = "Quand le statut est 'PAYÉ', la date de paiement doit être aujourd'hui ("
                                . $aujourdhui->format('d/m/Y') . ").";
                }
            }

            if (empty($errors)) {
                $salaire->setStatus($status);
                $salaire->setDatePaiement(new \DateTime($datePaiement));
                $salaire->setUpdatedAt(new \DateTime());

                $em->flush();

                $this->addFlash('success', 'Salaire modifié avec succès');
                return $this->redirectToRoute('salaire_index');
            }
        }

        return $this->render('salaire/edit.html.twig', [
            'salaire' => $salaire,
            'errors'  => $errors,
        ]);
    }

    #[Route('/{id}/delete', name: 'salaire_delete', methods: ['POST'])]
    public function delete(Salaire $salaire, EntityManagerInterface $em): Response
    {
        // ⭐ Bloquer suppression si PAYÉ
        if ($salaire->getStatus() === 'PAYÉ') {
            $this->addFlash('error', 'Impossible de supprimer un salaire déjà payé.');
            return $this->redirectToRoute('salaire_index');
        }

        $em->remove($salaire);
        $em->flush();

        $this->addFlash('success', 'Salaire supprimé');
        return $this->redirectToRoute('salaire_index');
    }
}