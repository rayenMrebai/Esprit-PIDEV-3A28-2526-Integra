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
    #[Route('/new', name: 'bonus_rule_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SalaireRepository $salaireRepo
    ): Response {
        $rule = new BonusRule();

        // 🔥 récupérer salaire depuis URL
        $salaireId = $request->query->get('salaireId');
        if ($salaireId) {
            $salaire = $salaireRepo->find($salaireId);
            if ($salaire) {
                $rule->setSalaire($salaire);
            }
        }

        // 🔥 Form sans status (ADD)
        $form = $this->createForm(BonusRuleType::class, $rule, [
            'is_edit' => false
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ❌ sécurité salaire payé
            if ($rule->getSalaire()->getStatus() === 'PAYÉ') {
                $this->addFlash('error', 'Impossible d’ajouter une règle à un salaire payé');
                return $this->redirectToRoute('app_backoffice_salaires_index');
            }

            // 🔥 status automatique
            $rule->setStatus('CRÉE');

            // 🔥 calcul bonus
            $rule->recalculateBonus();

            $rule->setCreatedAt(new \DateTime());
            $rule->setUpdatedAt(new \DateTime());

            $em->persist($rule);
            $em->flush();

            $this->addFlash('success', 'Règle ajoutée');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        return $this->render('backoffice/salaires/bonus/add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}/edit', name: 'bonus_rule_edit')]
    public function edit(
        Request $request,
        BonusRule $rule,
        EntityManagerInterface $em
    ): Response {

        // ❌ sécurité règle ACTIVE
        if ($rule->getStatus() === 'ACTIVE') {
            $this->addFlash('error', 'Impossible de modifier une règle active');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        // ❌ sécurité salaire PAYÉ
        if ($rule->getSalaire()->getStatus() === 'PAYÉ') {
            $this->addFlash('error', 'Impossible de modifier une règle d’un salaire payé');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        // 🔥 Form avec status (EDIT)
        $form = $this->createForm(BonusRuleType::class, $rule, [
            'is_edit' => true
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $rule->recalculateBonus();
            $rule->setUpdatedAt(new \DateTime());

            // 🔥 si ACTIVE → recalcul salaire
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
                $salaire->setUpdatedAt(new \DateTime());
            }

            $em->flush();

            $this->addFlash('success', 'Règle modifiée');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        return $this->render('backoffice/salaires/bonus/editRule.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}/delete', name: 'bonus_rule_delete', methods: ['POST'])]
    public function delete(BonusRule $rule, EntityManagerInterface $em): Response
    {
        // ❌ règle ACTIVE
        if ($rule->getStatus() === 'ACTIVE') {
            $this->addFlash('error', 'Impossible de supprimer une règle active');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        // ❌ salaire PAYÉ
        if ($rule->getSalaire()->getStatus() === 'PAYÉ') {
            $this->addFlash('error', 'Impossible de supprimer une règle d’un salaire payé');
            return $this->redirectToRoute('app_backoffice_salaires_index');
        }

        $em->remove($rule);
        $em->flush();

        $this->addFlash('success', 'Règle supprimée');
        return $this->redirectToRoute('app_backoffice_salaires_index');
    }
}