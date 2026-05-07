<?php

namespace App\Service;

use App\Entity\Candidat;

class CandidatValidator
{
    /**
     * Valide les données d'un candidat selon les règles métier définies.
     *
     * @throws \InvalidArgumentException
     */
    public function validate(Candidat $candidat): bool
    {
        // Règle 1 : Le prénom est obligatoire
        if (empty($candidat->getFirstName())) {
            throw new \InvalidArgumentException('Le prénom du candidat est obligatoire.');
        }

        // Règle 2 : Le nom est obligatoire
        if (empty($candidat->getLastName())) {
            throw new \InvalidArgumentException('Le nom du candidat est obligatoire.');
        }

        // Règle 3 : L'email doit être valide
        if (!filter_var($candidat->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('L\'email du candidat doit être valide.');
        }

        // Règle 4 : Le téléphone (optionnel) doit être un nombre positif s'il est renseigné
        $phone = $candidat->getPhone();
        if ($phone !== null && $phone !== 0 && (!is_numeric($phone) || $phone < 0)) {
            throw new \InvalidArgumentException('Le téléphone doit être un nombre valide.');
        }

        // Règle 5 : Le statut doit faire partie des valeurs autorisées
        $allowedStatuses = ['Nouveau', 'En revue', 'Accepté', 'Rejeté'];
        if (!in_array($candidat->getStatus(), $allowedStatuses)) {
            throw new \InvalidArgumentException('Le statut du candidat n\'est pas valide.');
        }

        return true;
    }
}