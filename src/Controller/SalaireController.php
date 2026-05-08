<?php

namespace App\Controller;

use App\Entity\Salaire;
use App\Form\SalaireType;
use App\Form\SalaireEditType;
use App\Repository\SalaireRepository;
use App\Repository\UserAccountRepository;
use App\Service\EmailSalaireService;
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/salaire')]
class SalaireController extends AbstractController
{
    public function __construct(
        private EmailSalaireService $emailService,
        private PdfService   $pdfService
    ) {}

    #[Route('/', name: 'salaire_index', methods: ['GET'])]
    public function index(SalaireRepository $repo): Response
    {
        return $this->render('salaire/index.html.twig', [
            'salaires' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'salaire_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $salaire = new Salaire();
        $form    = $this->createForm(SalaireType::class, $salaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $salaire->setTotalAmount($salaire->getBaseAmount() + $salaire->getBonusAmount());
            $salaire->setStatus('CREÉ');

            $em->persist($salaire);
            $em->flush();

            // ✅ Envoi email création
            try {
                $this->emailService->sendSalaireCreated($salaire);
                $this->addFlash('success', 'Salaire créé et email envoyé avec succès');
            } catch (\Exception $e) {
                // Ne pas bloquer si l'email échoue
                $this->addFlash('warning', 'Salaire créé mais l\'email n\'a pas pu être envoyé.');
            }

            $this->addFlash('success', 'Salaire créé avec succès');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        return $this->render('backoffice/salaires/salaire/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id<\d+>}', name: 'salaire_show', methods: ['GET'])]
    public function show(Salaire $salaire): Response
    {
        return $this->render('salaire/show.html.twig', [
            'salaire' => $salaire,
        ]);
    }

    #[Route('/{id<\d+>}/download-pdf', name: 'salaire_download_pdf', methods: ['GET'])]
    public function downloadPdf(Salaire $salaire): Response
    {
        $pdfContent = $this->pdfService->generatePayslipPdf($salaire);
        $fileName   = $this->pdfService->getPayslipFileName($salaire);

        return new Response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    #[Route('/{id<\d+>}/edit', name: 'salaire_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Salaire $salaire, EntityManagerInterface $em): Response
    {
        if ($salaire->getStatus() === 'PAYÉ') {
            $this->addFlash('error', 'Impossible de modifier un salaire déjà payé.');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        $oldStatus = $salaire->getStatus();
        $form      = $this->createForm(SalaireEditType::class, $salaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($salaire->getStatus() === 'PAYÉ') {
                $datePaiement = $salaire->getDatePaiement();
                // ✅ Vérification null avant format()
                if ($datePaiement === null) {
                    $this->addFlash('error', "La date de paiement est obligatoire pour le statut PAYÉ.");
                    return $this->render('backoffice/salaires/salaire/edit.html.twig', [
                        'form'    => $form->createView(),
                        'salaire' => $salaire,
                    ]);
                }
                $today = new \DateTime('today');
                if ($datePaiement->format('Y-m-d') !== $today->format('Y-m-d')) {
                    $this->addFlash('error', "Quand le statut est 'PAYÉ', la date de paiement doit être aujourd'hui.");
                    return $this->render('backoffice/salaires/salaire/edit.html.twig', [
                        'form'    => $form->createView(),
                        'salaire' => $salaire,
                    ]);
                }
            }

            $salaire->setTotalAmount($salaire->getBaseAmount() + $salaire->getBonusAmount());
            $salaire->setUpdatedAt(new \DateTime());

            $em->flush();

            // ✅ Envoi email si passage à PAYÉ
            if ($oldStatus !== 'PAYÉ' && $salaire->getStatus() === 'PAYÉ') {
                try {
                    $this->emailService->sendSalairePaid($salaire);
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Salaire mis à jour mais l\'email n\'a pas pu être envoyé.');
                }
            }

            $this->addFlash('success', 'Salaire modifié avec succès');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        return $this->render('backoffice/salaires/salaire/edit.html.twig', [
            'form'    => $form->createView(),
            'salaire' => $salaire,
        ]);
    }

    #[Route('/{id<\d+>}/delete', name: 'salaire_delete', methods: ['POST'])]
    public function delete(Request $request, Salaire $salaire, EntityManagerInterface $em): Response
    {
        if ($salaire->getStatus() === 'PAYÉ') {
            $this->addFlash('error', 'Impossible de supprimer un salaire déjà payé.');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        $token = (string) $request->request->get('_token'); // ✅ cast string
        if (!$this->isCsrfTokenValid('delete' . $salaire->getId(), $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        $em->remove($salaire);
        $em->flush();
        $this->addFlash('success', 'Salaire supprimé');

        return $this->redirectToRoute('app_backoffice_salaires_index');
    }
}