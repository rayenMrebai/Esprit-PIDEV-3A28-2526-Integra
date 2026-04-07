<?php

namespace App\Controller;

use App\Entity\BonusRule;
use App\Repository\BonusRuleRepository;
use App\Repository\SalaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bonus-rule')]
class BonusRuleController extends AbstractController
{
    #[Route('/', name: 'bonus_rule_index', methods: ['GET'])]
    public function index(BonusRuleRepository $repo): Response
    {
        return $this->render('bonus_rule/index.html.twig', [
            'bonus_rules' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'bonus_rule_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SalaireRepository $salaireRepo): Response
    {
        $errors = [];

        if ($request->isMethod('POST')) {
            $salaireId     = $request->request->get('salaireId');
            $nomRegle      = $request->request->get('nomRegle');
            $percentage    = $request->request->get('percentage');
            $conditionText = $request->request->get('conditionText');

            if (empty($salaireId)) {
                $errors[] = "Le salaire est obligatoire.";
            }

            if (empty($nomRegle)) {
                $errors[] = "Le nom de la règle est obligatoire.";
            } elseif (strlen($nomRegle) < 3) {
                $errors[] = "Le nom de la règle doit avoir au moins 3 caractères.";
            } elseif (strlen($nomRegle) > 100) {
                $errors[] = "Le nom de la règle ne doit pas dépasser 100 caractères.";
            }

            
            if ($percentage === '' || $percentage === null) {
                $errors[] = "Le pourcentage est obligatoire.";
            } elseif (!is_numeric($percentage)) {
                $errors[] = "Le pourcentage doit être un nombre.";
            } elseif ((float)$percentage < 0) {
                $errors[] = "Le pourcentage ne peut pas être négatif.";
            } elseif ((float)$percentage > 100) {
                $errors[] = "Le pourcentage ne peut pas dépasser 100%.";
            }

            
            $salaire = !empty($salaireId) ? $salaireRepo->find($salaireId) : null;
            if (!$salaire) {
                $errors[] = "Le salaire sélectionné n'existe pas.";
            }

            
            if ($salaire && $salaire->getStatus() === 'PAYÉ') {
                $errors[] = "Impossible d'ajouter une règle à un salaire déjà payé.";
            }

            if (empty($errors)) {
                $percentage = (float)$percentage;

                $rule = new BonusRule();
                $rule->setSalaire($salaire);
                $rule->setNomRegle($nomRegle);
                $rule->setPercentage($percentage);
                $rule->setBonus($salaire->getBaseAmount() * ($percentage / 100));
                $rule->setConditionText($conditionText);
                $rule->setStatus('CRÉE'); 

                $em->persist($rule);
                $em->flush();

                $this->addFlash('success', 'Règle de bonus créée avec succès');
                return $this->redirectToRoute('bonus_rule_index');
            }
        }

        return $this->render('bonus_rule/new.html.twig', [
            'salaires' => $salaireRepo->findAll(),
            'errors'   => $errors,
        ]);
    }

    #[Route('/{id}', name: 'bonus_rule_show', methods: ['GET'])]
    public function show(BonusRule $rule): Response
    {
        return $this->render('bonus_rule/show.html.twig', [
            'rule' => $rule,
        ]);
    }

    #[Route('/{id}/edit', name: 'bonus_rule_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, BonusRule $rule, EntityManagerInterface $em): Response
    {
        
        if ($rule->getStatus() === 'ACTIVE') {
            $this->addFlash('error', 'Impossible de modifier une règle déjà active.');
            return $this->redirectToRoute('bonus_rule_index');
        }

        
        if ($rule->getSalaire()->getStatus() === 'PAYÉ') {
            $this->addFlash('error', 'Impossible de modifier une règle d\'un salaire déjà payé.');
            return $this->redirectToRoute('bonus_rule_index');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $nomRegle      = $request->request->get('nomRegle');
            $percentage    = $request->request->get('percentage');
            $conditionText = $request->request->get('conditionText');
            $status        = $request->request->get('status');

            
            if (empty($nomRegle)) {
                $errors[] = "Le nom de la règle est obligatoire.";
            } elseif (strlen($nomRegle) < 3) {
                $errors[] = "Le nom de la règle doit avoir au moins 3 caractères.";
            } elseif (strlen($nomRegle) > 100) {
                $errors[] = "Le nom de la règle ne doit pas dépasser 100 caractères.";
            }

            if ($percentage === '' || $percentage === null) {
                $errors[] = "Le pourcentage est obligatoire.";
            } elseif (!is_numeric($percentage)) {
                $errors[] = "Le pourcentage doit être un nombre.";
            } elseif ((float)$percentage < 0) {
                $errors[] = "Le pourcentage ne peut pas être négatif.";
            } elseif ((float)$percentage > 100) {
                $errors[] = "Le pourcentage ne peut pas dépasser 100%.";
            }

            
            if (empty($status)) {
                $errors[] = "Le statut est obligatoire.";
            } elseif (!in_array($status, ['CRÉE', 'ACTIVE'])) {
                $errors[] = "Le statut est invalide.";
            }

            if (empty($errors)) {
                $percentage = (float)$percentage;

                $rule->setNomRegle($nomRegle);
                $rule->setPercentage($percentage);
                $rule->setBonus($rule->getSalaire()->getBaseAmount() * ($percentage / 100));
                $rule->setConditionText($conditionText);
                $rule->setStatus($status);
                $rule->setUpdatedAt(new \DateTime());

                if ($status === 'ACTIVE') {
                    $salaire    = $rule->getSalaire();
                    $totalBonus = 0;

                    foreach ($salaire->getBonusRules() as $r) {
                        $rStatus = ($r->getId() === $rule->getId()) ? 'ACTIVE' : $r->getStatus();
                        if ($rStatus === 'ACTIVE') {
                            $totalBonus += $r->getBonus();
                        }
                    }

                    $salaire->setBonusAmount($totalBonus);
                    $salaire->setTotalAmount($salaire->getBaseAmount() + $totalBonus);
                    $salaire->setUpdatedAt(new \DateTime());
                }

                $em->flush();

                $this->addFlash('success', 'Règle modifiée avec succès');
                return $this->redirectToRoute('bonus_rule_index');
            }
        }

        return $this->render('bonus_rule/edit.html.twig', [
            'rule'   => $rule,
            'errors' => $errors,
        ]);
    }

    #[Route('/{id}/delete', name: 'bonus_rule_delete', methods: ['POST'])]
    public function delete(BonusRule $rule, EntityManagerInterface $em): Response
    {
        if ($rule->getStatus() === 'ACTIVE') {
            $this->addFlash('error', 'Impossible de supprimer une règle déjà active.');
            return $this->redirectToRoute('bonus_rule_index');
        }

        if ($rule->getSalaire()->getStatus() === 'PAYÉ') {
            $this->addFlash('error', 'Impossible de supprimer une règle d\'un salaire déjà payé.');
            return $this->redirectToRoute('bonus_rule_index');
        }

        $em->remove($rule);
        $em->flush();

        $this->addFlash('success', 'Règle supprimée');
        return $this->redirectToRoute('bonus_rule_index');
    }
}