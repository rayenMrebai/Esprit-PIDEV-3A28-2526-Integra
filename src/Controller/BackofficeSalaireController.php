<?php

namespace App\Controller;

use App\Repository\SalaireRepository;
use App\Repository\BonusRuleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\UserAccount;

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
        $search = $request->query->get('search', '');
        $hasSearch = !empty($search);

        
        $filterStatus = $request->query->get('filter_status', '');
        $hasFilter = !empty($filterStatus);

        
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
            'salaires'      => $salaires,
            'bonusRulesMap' => $bonusRulesMap,
            'salaireUrls'   => $salaireUrls,
            'search'        => $search,
            'hasSearch'     => $hasSearch,
            'filterStatus'  => $filterStatus,
            'hasFilter'     => $hasFilter,
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
        
        // Récupérer UNIQUEMENT les salaires du manager connecté
        $salaires = $salaireRepo->findBy(
            ['user' => $user], 
            ['datePaiement' => 'DESC']
        );
        
        // Calculs statistiques
        $totalPercu = 0;
        $totalBonus = 0;
        $nbPayes = 0;
        
        foreach ($salaires as $salaire) {
            $totalPercu += $salaire->getTotalAmount();
            $totalBonus += $salaire->getBonusAmount();
            if ($salaire->getStatus() === 'PAYÉ') {
                $nbPayes++;
            }
        }
        
        $moyenne = count($salaires) > 0 ? $totalPercu / count($salaires) : 0;
        
        // Préparer les URLs pour les actions (si nécessaire)
        $salaireUrls = [];
        foreach ($salaires as $salaire) {
            $salaireUrls[$salaire->getId()] = [
                'show' => $this->generateUrl('salaire_show', ['id' => $salaire->getId()]),
                'pdf'  => $this->generateUrl('salaire_download_pdf', ['id' => $salaire->getId()])
            ];
        }
        
        // Bonus rules map
        $bonusRulesMap = [];
        foreach ($salaires as $salaire) {
            $rules = $bonusRuleRepo->findBy(['salaire' => $salaire->getId()]);
            $bonusRulesMap[$salaire->getId()] = array_map(fn($r) => [
                'id' => $r->getId(),
                'nomRegle' => $r->getNomRegle(),
                'percentage' => $r->getPercentage(),
                'conditionText' => $r->getConditionText(),
                'status' => $r->getStatus(),
            ], $rules);
        }

        return $this->render('backoffice/salaires/mes_salaires.html.twig', [
            'salaires' => $salaires,
            'bonusRulesMap' => $bonusRulesMap,
            'salaireUrls' => $salaireUrls,
            'totalPercu' => $totalPercu,
            'totalBonus' => $totalBonus,
            'nbPayes' => $nbPayes,
            'moyenne' => $moyenne,
            'nbTotal' => count($salaires),
            'user' => $user,
        ]);
    }
}