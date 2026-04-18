<?php

namespace App\Controller;

use App\Service\ExcelExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backoffice/salaires')]
#[IsGranted('ROLE_MANAGER')]
class ExcelExportController extends AbstractController
{
    public function __construct(
        private ExcelExportService $excelService
    ) {}

    #[Route('/export-excel', name: 'salaires_export_excel', methods: ['GET', 'POST'])]
    public function export(Request $request): Response
    {
        // GET → afficher le modal (rien à faire, le modal est dans index.html.twig)
        // POST → générer le fichier
        if ($request->isMethod('POST')) {
            $filters = [
                'periode_type'         => $request->request->get('periode_type', 'TOUS'),
                'mois'                 => (int)$request->request->get('mois', date('n')),
                'annee'                => (int)$request->request->get('annee', date('Y')),
                'date_debut'           => $request->request->get('date_debut'),
                'date_fin'             => $request->request->get('date_fin'),
                'status_paye'          => $request->request->get('status_paye'),
                'status_en_cours'      => $request->request->get('status_en_cours'),
                'status_cree'          => $request->request->get('status_cree'),
                'montant_min'          => (float)$request->request->get('montant_min', 0),
                'inclure_statistiques' => $request->request->get('inclure_statistiques'),
                'inclure_bonus'        => $request->request->get('inclure_bonus'),
                'appliquer_formatage'  => $request->request->get('appliquer_formatage'),
            ];

            $content  = $this->excelService->export($filters);
            $filename = $this->excelService->getFilename($filters);

            return new Response($content, 200, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control'       => 'max-age=0',
            ]);
        }

        // GET sans POST → rediriger vers l'index
        return $this->redirectToRoute('app_backoffice_salaires_index');
    }
}