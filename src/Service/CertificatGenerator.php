<?php
// src/Service/CertificatGenerator.php

namespace App\Service;

use App\Entity\Quiz_result;
use Dompdf\Dompdf;
use Dompdf\Options;

class CertificatGenerator
{
    public function generate(Quiz_result $quiz): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        $user      = $quiz->getUser();
        $formation = $quiz->getTraining();

        $html = $this->buildHtml(
            $user->getUsername(),
            $formation->getTitle(),
            $quiz->getPercentage(),
            new \DateTime()
        );

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output(); // retourne le PDF en string
    }

    private function buildHtml(
        string $userName,
        string $formationTitle,
        float $percentage,
        \DateTime $date
    ): string {
        return sprintf('
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: "Georgia", serif;
            background: #fff;
            width: 297mm;
            height: 210mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .certificat {
            width: 270mm;
            height: 190mm;
            border: 12px solid #1a3c6e;
            padding: 20mm 25mm;
            text-align: center;
            position: relative;
            background: linear-gradient(135deg, #fefefe 0%%, #f0f4ff 100%%);
        }

        .certificat::before {
            content: "";
            position: absolute;
            top: 8px; left: 8px; right: 8px; bottom: 8px;
            border: 2px solid #c9a84c;
            pointer-events: none;
        }

        .logo-title {
            font-size: 13pt;
            color: #1a3c6e;
            letter-spacing: 4px;
            text-transform: uppercase;
            margin-bottom: 8mm;
        }

        .main-title {
            font-size: 32pt;
            color: #c9a84c;
            margin-bottom: 6mm;
            font-style: italic;
        }

        .subtitle {
            font-size: 12pt;
            color: #555;
            margin-bottom: 10mm;
        }

        .name {
            font-size: 26pt;
            color: #1a3c6e;
            font-weight: bold;
            border-bottom: 2px solid #c9a84c;
            display: inline-block;
            padding-bottom: 2mm;
            margin-bottom: 8mm;
        }

        .formation {
            font-size: 15pt;
            color: #333;
            margin-bottom: 4mm;
        }

        .formation span {
            color: #1a3c6e;
            font-weight: bold;
        }

        .score {
            font-size: 12pt;
            color: #555;
            margin-bottom: 10mm;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 8mm;
        }

        .date {
            font-size: 11pt;
            color: #777;
        }

        .signature {
            font-size: 11pt;
            color: #1a3c6e;
            border-top: 1px solid #1a3c6e;
            padding-top: 2mm;
            min-width: 50mm;
            text-align: center;
        }

        .seal {
            width: 25mm;
            height: 25mm;
            border-radius: 50%%;
            border: 3px solid #c9a84c;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8pt;
            color: #c9a84c;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: center;
            padding: 3mm;
        }
    </style>
</head>
<body>
<div class="certificat">
    <div class="logo-title">🎓 Plateforme de Formation Professionnelle</div>

    <div class="main-title">Certificat de Réussite</div>

    <div class="subtitle">Ce certificat est décerné à</div>

    <div class="name">%s</div>

    <div class="formation">
        Pour avoir complété avec succès la formation<br>
        <span>%s</span>
    </div>

    <div class="score">Score obtenu : %.0f%%</div>

    <div class="footer">
        <div class="date">
            Délivré le<br>
            <strong>%s</strong>
        </div>
        <div class="seal">Certifié<br>✓<br>Validé</div>
        <div class="signature">
            Directeur de Formation
        </div>
    </div>
</div>
</body>
</html>',
            htmlspecialchars($userName),
            htmlspecialchars($formationTitle),
            $percentage,
            $date->format('d/m/Y')
        );
    }
}