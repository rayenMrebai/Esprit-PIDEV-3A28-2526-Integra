<?php

namespace App\Service;

use App\Entity\Salaire;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfService
{
    public function __construct(
        private Environment           $twig,
        private PayslipCodeGenerator  $codeGenerator,
        private string                $projectDir
    ) {}

    public function generatePayslipPdf(Salaire $salaire): string
    {
        $code = $this->codeGenerator->generate($salaire);

        $html = $this->twig->render('pdf/fiche_paie.html.twig', [
            'salaire'     => $salaire,
            'code'        => $code,
            'generatedAt' => new \DateTime(),
            'logoBase64'  => '',  // ← vide, pas de logo
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function getPayslipFileName(Salaire $salaire): string
    {
        return $this->codeGenerator->generateFileName($salaire);
    }
}