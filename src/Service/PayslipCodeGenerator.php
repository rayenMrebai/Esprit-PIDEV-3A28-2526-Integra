<?php

namespace App\Service;

use App\Entity\Salaire;

class PayslipCodeGenerator
{
    public function generate(Salaire $salaire): string
    {
        $datePart  = (new \DateTime())->format('Ymd');
        $namePart  = strtoupper(preg_replace('/\s+/', '', $salaire->getUser()->getUsername()));
        $uniquePart = strtoupper(substr(str_replace('-', '', bin2hex(random_bytes(4))), 0, 8));

        return 'FP-' . $namePart . '-' . $datePart . '-' . $uniquePart;
    }

    public function generateFileName(Salaire $salaire): string
    {
        $name = preg_replace('/\s+/', '_', $salaire->getUser()->getUsername());
        $date = (new \DateTime())->format('Y-m-d');
        return 'fiche_paie_' . $name . '_' . $date . '.pdf';
    }
}