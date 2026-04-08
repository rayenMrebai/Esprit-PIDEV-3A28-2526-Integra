<?php

namespace App\Controller;

use App\Repository\SalaireRepository;
use App\Repository\BonusRuleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/backoffice/salaires', name: 'app_backoffice_salaires_')]
class BackofficeSalaireController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        Request $request,
        SalaireRepository $salaireRepo,
        BonusRuleRepository $bonusRuleRepo,
        CsrfTokenManagerInterface $csrf
    ): Response {
        // ⭐ RECHERCHE (par nom employé)
        $search = $request->query->get('search', '');
        $hasSearch = !empty($search);

        // ⭐ FILTRAGE (statut uniquement)
        $filterStatus = $request->query->get('filter_status', '');
        $hasFilter = !empty($filterStatus);

        // ⭐ Logique combinée
        if ($hasSearch && $hasFilter) {
            // Recherche + Filtrage par statut
            $salaires = $salaireRepo->findBySearchAndStatus($search, $filterStatus);
        } elseif ($hasSearch) {
            // Uniquement recherche
            $salaires = $salaireRepo->findByUsernameSearch($search);
        } elseif ($hasFilter) {
            // Uniquement filtrage par statut
            $salaires = $salaireRepo->findByStatus($filterStatus);
        } else {
            // Aucun critère - afficher tout
            $salaires = $salaireRepo->findAll();
        }

        // ── bonusRulesMap pour le JS ──
        $bonusRulesMap = [];
        foreach ($salaires as $salaire) {
            $rules = $bonusRuleRepo->findBy(['salaire' => $salaire->getId()]);
            $bonusRulesMap[$salaire->getId()] = array_map(fn($r) => [
                'id'            => $r->getId(),
                'nomRegle'      => $r->getNomRegle(),
                'percentage'    => $r->getPercentage(),
                'conditionText' => $r->getConditionText(),
                'status'        => $r->getStatus(),
                'editUrl'       => $this->generateUrl('bonus_rule_edit', ['id' => $r->getId()]),
                'deleteUrl'     => $this->generateUrl('bonus_rule_delete', ['id' => $r->getId()]),
                'deleteToken'   => $csrf->getToken('delete' . $r->getId())->getValue(),
            ], $rules);
        }

        // ── salaireUrls pour le JS ──
        $salaireUrls = [];
        foreach ($salaires as $salaire) {
            $salaireUrls[$salaire->getId()] = [
                'edit'        => $this->generateUrl('salaire_edit', ['id' => $salaire->getId()]),
                'show'        => $this->generateUrl('salaire_show', ['id' => $salaire->getId()]),
                'delete'      => $this->generateUrl('salaire_delete', ['id' => $salaire->getId()]),
                'deleteToken' => $csrf->getToken('delete' . $salaire->getId())->getValue(),
            ];
        }

        return $this->render('backoffice/salaires/index.html.twig', [
            'salaires'      => $salaires,
            'bonusRulesMap' => $bonusRulesMap,
            'salaireUrls'   => $salaireUrls,
            'search'        => $search,
            'hasSearch'     => $hasSearch,
            'filterStatus'  => $filterStatus,
            'hasFilter'     => $hasFilter,
        ]);
    }
}