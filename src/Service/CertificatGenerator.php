<?php

namespace App\Service;

use App\Entity\Quiz_result;  // ← Utilisez Quiz_result
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class CertificatGenerator
{
    public function __construct(
        private Environment $twig
    ) {}

    public function generate(Quiz_result $quiz): string  // ← Modifiez ici aussi
    {
        $user = $quiz->getUser();
        $formation = $quiz->getTraining();
        
        $html = $this->twig->render('certificat/pdf.html.twig', [
            'user' => $user,
            'formation' => $formation,
            'quiz' => $quiz,
            'percentage' => $quiz->getPercentage(),
            'date' => new \DateTime()
        ]);
        
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        return $dompdf->output();
    }
}