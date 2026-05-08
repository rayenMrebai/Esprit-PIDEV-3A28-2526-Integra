<?php

namespace App\Service;

use App\Entity\Salaire;

class PayslipCodeGenerator
{
    public function generate(Salaire $salaire): string
    {
        $user = $salaire->getUser();
        if ($user === null) {
            throw new \RuntimeException('Utilisateur non trouvé');
        }

        $username = $user->getUsername();
        if ($username === null) {
            $username = 'UNKNOWN';
        }

        $datePart = (new \DateTime())->format('Ymd');

        $namePart = strtoupper(
            (string) preg_replace('/\s+/', '', $username)
        );

        $uniquePart = strtoupper(
            substr(str_replace('-', '', bin2hex(random_bytes(4))), 0, 8)
        );

        return 'FP-' . $namePart . '-' . $datePart . '-' . $uniquePart;
    }

    public function generateFileName(Salaire $salaire): string
    {
        $user = $salaire->getUser();
        if ($user === null) {
            throw new \RuntimeException('Utilisateur non trouvé');
        }

        $username = $user->getUsername();
        if ($username === null) {
            $username = 'UNKNOWN';
        }

        $name = preg_replace('/\s+/', '_', $username);
        $date = (new \DateTime())->format('Y-m-d');

        return 'fiche_paie_' . $name . '_' . $date . '.pdf';
    }
}