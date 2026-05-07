<?php

namespace App\Service;

use App\Entity\Jobposition;

class JobpositionValidator
{
    /**
     * Valide les données d'une offre d'emploi selon les règles métier définies.
     *
     * @throws \InvalidArgumentException
     */
    public function validate(Jobposition $jobposition): bool
    {
        // Règle 1 : Le titre est obligatoire
        if (empty($jobposition->getTitle())) {
            throw new \InvalidArgumentException('Le titre de l\'offre est obligatoire.');
        }

        // Règle 2 : Le département est obligatoire
        if (empty($jobposition->getDepartement())) {
            throw new \InvalidArgumentException('Le département est obligatoire.');
        }

        // Règle 3 : Le type d'emploi est obligatoire
        if (empty($jobposition->getEmployeeType())) {
            throw new \InvalidArgumentException('Le type d\'emploi est obligatoire.');
        }

        // Règle 4 : La description est obligatoire et doit faire au moins 10 caractères
        $description = $jobposition->getDescription();
        if (empty($description) || strlen($description) < 10) {
            throw new \InvalidArgumentException('La description doit contenir au moins 10 caractères.');
        }

        // Règle 5 : La date de publication est obligatoire
        if ($jobposition->getPostedAt() === null) {
            throw new \InvalidArgumentException('La date de publication est obligatoire.');
        }

        // Règle 6 : Le statut doit être 'Open' ou 'Closed'
        $allowedStatuses = ['Open', 'Closed'];
        if (!in_array($jobposition->getStatus(), $allowedStatuses)) {
            throw new \InvalidArgumentException('Le statut doit être "Open" ou "Closed".');
        }

        return true;
    }
}