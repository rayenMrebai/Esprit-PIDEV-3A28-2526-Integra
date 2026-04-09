<?php

namespace App\Controller;

use App\Entity\BonusRule;
use App\Form\BonusRuleType;
use App\Repository\SalaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bonus-rule')]
class BonusRuleController extends AbstractController
{
    #[Route('/new', name: 'bonus_rule_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SalaireRepository $salaireRepo
    ): Response {
        $rule = new BonusRule();

        // ✅ CONTRÔLE MÉTIER: Vérifier paramètre URL présent
        $salaireId = $request->query->get('salaireId');
        if (!$salaireId) {
            $this->addFlash('error', 'ID du salaire manquant dans l\'URL.');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        // ✅ CONTRÔLE MÉTIER: Vérifier salaire existe
        $salaire = $salaireRepo->find($salaireId);
        if (!$salaire) {
            $this->addFlash('error', 'Salaire introuvable.');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        // ✅ CONTRÔLE MÉTIER: Vérifier salaire pas déjà payé
        if ($salaire->getStatus() === 'PAYÉ') {
            $this->addFlash('error', 'Impossible d\'ajouter une règle à un salaire déjà payé.');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        $rule->setSalaire($salaire);

        $form = $this->createForm(BonusRuleType::class, $rule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ✅ CONTRÔLE MÉTIER: Double vérification sécurité (race condition)
            if ($rule->getSalaire()->getStatus() === 'PAYÉ') {
                $this->addFlash('error', 'Impossible d\'ajouter une règle à un salaire payé.');
                return $this->redirectToRoute('app_backoffice_salaires_index');
            }

            $rule->recalculateBonus();

            // ✅ CONTRÔLE MÉTIER: Vérifier bonus calculé valide
            if ($rule->getBonus() < 0) {
                $this->addFlash('error', 'Le bonus calculé ne peut pas être négatif.');
                return $this->render('backoffice/salaires/bonus/add.html.twig', [
                    'form' => $form->createView()
                ]);
            }

            $rule->setCreatedAt(new \DateTime());
            $rule->setUpdatedAt(new \DateTime());

            $em->persist($rule);
            $em->flush();

            $this->addFlash('success', 'Règle ajoutée avec succès');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        return $this->render('backoffice/salaires/bonus/add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id<\d+>}/edit', name: 'bonus_rule_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        BonusRule $rule,
        EntityManagerInterface $em
    ): Response {
        // ✅ CONTRÔLE MÉTIER: Vérifier règle pas déjà active
        if ($rule->getStatus() === 'ACTIVE') {
            $this->addFlash('error', 'Impossible de modifier une règle déjà active.');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        // ✅ CONTRÔLE MÉTIER: Vérifier salaire pas payé
        if ($rule->getSalaire()->getStatus() === 'PAYÉ') {
            $this->addFlash('error', 'Impossible de modifier une règle d\'un salaire déjà payé.');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        $form = $this->createForm(BonusRuleType::class, $rule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rule->recalculateBonus();

            // ✅ CONTRÔLE MÉTIER: Vérifier bonus calculé valide
            if ($rule->getBonus() < 0) {
                $this->addFlash('error', 'Le bonus calculé ne peut pas être négatif.');
                return $this->render('backoffice/salaires/bonus/editRule.html.twig', [
                    'form' => $form->createView()
                ]);
            }

            $rule->setUpdatedAt(new \DateTime());

            // ✅ CONTRÔLE MÉTIER: Si activation, recalculer total salaire
            if ($rule->getStatus() === 'ACTIVE') {
                $salaire = $rule->getSalaire();
                $totalBonus = 0;

                foreach ($salaire->getBonusRules() as $r) {
                    if ($r->getStatus() === 'ACTIVE') {
                        $totalBonus += $r->getBonus();
                    }
                }

                $salaire->setBonusAmount($totalBonus);
                $salaire->setTotalAmount($salaire->getBaseAmount() + $totalBonus);
            }

            $em->flush();

            $this->addFlash('success', 'Règle modifiée avec succès');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        return $this->render('backoffice/salaires/bonus/editRule.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id<\d+>}/delete', name: 'bonus_rule_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        BonusRule $rule,
        EntityManagerInterface $em
    ): Response {
        // ✅ CONTRÔLE SÉCURITÉ: Token CSRF
        if (!$this->isCsrfTokenValid('delete' . $rule->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        // ✅ CONTRÔLE MÉTIER: Vérifier règle pas active
        if ($rule->getStatus() === 'ACTIVE') {
            $this->addFlash('error', 'Impossible de supprimer une règle active.');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        // ✅ CONTRÔLE MÉTIER: Vérifier salaire pas payé
        if ($rule->getSalaire()->getStatus() === 'PAYÉ') {
            $this->addFlash('error', 'Impossible de supprimer une règle d\'un salaire payé.');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        $em->remove($rule);
        $em->flush();

        $this->addFlash('success', 'Règle supprimée avec succès');
        return $this->redirectToRoute('app_backoffice_salaires_index');
    }
}