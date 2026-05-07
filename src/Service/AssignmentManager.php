<?php

namespace App\Service;

use App\Entity\Projectassignment;
use InvalidArgumentException;

class AssignmentManager
{
    /**
     * Valide les règles métier d'une affectation.
     *
     * @param Projectassignment $assignment
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validate(Projectassignment $assignment): bool
    {
        // Règle 1 : Le rôle est obligatoire
        if (empty($assignment->getRole())) {
            throw new InvalidArgumentException('Le rôle est obligatoire.');
        }

        // Règle 2 : Le taux d'allocation doit être compris entre 0 et 100
        $rate = $assignment->getAllocationRate();
        if ($rate < 0 || $rate > 100) {
            throw new InvalidArgumentException("Le taux d'allocation doit être compris entre 0 et 100.");
        }

        // Règle 3 : Le projet est obligatoire
        if ($assignment->getProject() === null) {
            throw new InvalidArgumentException('Le projet est obligatoire.');
        }

        // Règle 4 : L'employé est obligatoire
        if ($assignment->getUserAccount() === null) {
            throw new InvalidArgumentException("L'employé est obligatoire.");
        }

        return true;
    }
}