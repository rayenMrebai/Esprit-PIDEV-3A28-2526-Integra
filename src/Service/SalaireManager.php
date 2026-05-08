<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Salaire;

class SalaireManager
{
    /**
     * Valide les règles métier d'un salaire
     *
     * @throws \InvalidArgumentException
     */
    public function validate(Salaire $salaire): bool
    {
        // baseAmount est maintenant une chaîne (decimal)
        $baseAmount = $salaire->getBaseAmount();

        if ($baseAmount === '' || (float) $baseAmount <= 0) {
            throw new \InvalidArgumentException(
                'Le salaire de base doit être supérieur à zéro.'
            );
        }

        $datePaiement = $salaire->getDatePaiement();
        if ($datePaiement === null) {
            throw new \InvalidArgumentException(
                'La date de paiement est obligatoire.'
            );
        }

        $aujourdhui = new \DateTime('today');
        if ($datePaiement < $aujourdhui) {
            throw new \InvalidArgumentException(
                'La date de paiement ne peut pas être dans le passé.'
            );
        }

        $validStatuses = ['CREÉ', 'EN_COURS', 'PAYÉ'];
        if (!in_array($salaire->getStatus(), $validStatuses, true)) {
            throw new \InvalidArgumentException(
                'Le statut doit être : CREÉ, EN_COURS ou PAYÉ.'
            );
        }

        return true;
    }
}