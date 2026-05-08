<?php

namespace App\Controller;

use App\Entity\BonusRule;
use App\Entity\UserAccount;
use App\Repository\BonusRuleRepository;
use App\Repository\SalaireRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
        // ✅ Cast explicite en string pour satisfaire PHPStan
        $search      = (string) $request->query->get('search', '');
        $hasSearch   = $search !== '';
        $filterStatus = (string) $request->query->get('filter_status', '');
        $hasFilter   = $filterStatus !== '';

        if ($hasSearch && $hasFilter) {
            $salaires = $salaireRepo->findBySearchAndStatus($search, $filterStatus);
        } elseif ($hasSearch) {
            $salaires = $salaireRepo->findByUsernameSearch($search);
        } elseif ($hasFilter) {
            $salaires = $salaireRepo->findByStatus($filterStatus);
        } else {
            $salaires = $salaireRepo->findAll();
        }

        $bonusRulesMap = [];
        foreach ($salaires as $salaire) {
            // ✅ findBy avec type hint explicite → array<int, BonusRule>
            /** @var BonusRule[] $rules */
            $rules = $bonusRuleRepo->findBy(['salaire' => $salaire->getId()]);
            $bonusRulesMap[$salaire->getId()] = array_map(fn(BonusRule $r) => [
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

        $salaireUrls = [];
        foreach ($salaires as $salaire) {
            $salaireUrls[$salaire->getId()] = [
                'edit'        => $this->generateUrl('salaire_edit', ['id' => $salaire->getId()]),
                'show'        => $this->generateUrl('salaire_show', ['id' => $salaire->getId()]),
                'delete'      => $this->generateUrl('salaire_delete', ['id' => $salaire->getId()]),
                'deleteToken' => $csrf->getToken('delete' . $salaire->getId())->getValue(),
                'pdf'         => $this->generateUrl('salaire_download_pdf', ['id' => $salaire->getId()]),
            ];
        }

        return $this->render('backoffice/salaires/index.html.twig', [
            'salaires'     => $salaires,
            'bonusRulesMap' => $bonusRulesMap,
            'salaireUrls'  => $salaireUrls,
            'search'       => $search,
            'hasSearch'    => $hasSearch,
            'filterStatus' => $filterStatus,
            'hasFilter'    => $hasFilter,
        ]);
    }

    #[Route('/mes-salaires', name: 'mes_salaires')]
    #[IsGranted('ROLE_MANAGER')]
    public function mesSalaires(
        SalaireRepository $salaireRepo,
        BonusRuleRepository $bonusRuleRepo,
        CsrfTokenManagerInterface $csrf
    ): Response {
        /** @var UserAccount $user */
        $user = $this->getUser();

        $salaires   = $salaireRepo->findBy(['user' => $user], ['datePaiement' => 'DESC']);
        $totalPercu = 0.0;
        $totalBonus = 0.0;
        $nbPayes    = 0;

        foreach ($salaires as $salaire) {
            $totalPercu += (float) $salaire->getTotalAmount();
            $totalBonus += $salaire->getBonusAmount();
            if ($salaire->getStatus() === 'PAYÉ') {
                $nbPayes++;
            }
        }

        $nbTotal = count($salaires);
        $moyenne = $nbTotal > 0 ? $totalPercu / $nbTotal : 0.0;

        $salaireUrls = [];
        foreach ($salaires as $salaire) {
            $salaireUrls[$salaire->getId()] = [
                'show' => $this->generateUrl('salaire_show', ['id' => $salaire->getId()]),
                'pdf'  => $this->generateUrl('salaire_download_pdf', ['id' => $salaire->getId()]),
            ];
        }

        $bonusRulesMap = [];
        foreach ($salaires as $salaire) {
            /** @var BonusRule[] $rules */
            $rules = $bonusRuleRepo->findBy(['salaire' => $salaire->getId()]);
            $bonusRulesMap[$salaire->getId()] = array_map(fn(BonusRule $r) => [
                'id'            => $r->getId(),
                'nomRegle'      => $r->getNomRegle(),
                'percentage'    => $r->getPercentage(),
                'conditionText' => $r->getConditionText(),
                'status'        => $r->getStatus(),
            ], $rules);
        }

        return $this->render('backoffice/salaires/mes_salaires.html.twig', [
            'salaires'     => $salaires,
            'bonusRulesMap' => $bonusRulesMap,
            'salaireUrls'  => $salaireUrls,
            'totalPercu'   => $totalPercu,
            'totalBonus'   => $totalBonus,
            'nbPayes'      => $nbPayes,
            'moyenne'      => $moyenne,
            'nbTotal'      => $nbTotal,
            'user'         => $user,
        ]);
    }
}