<?php

namespace App\Service;

use App\Entity\Project;
use InvalidArgumentException;

class ProjectManager
{
    /**
     * Valide les règles métier d'un projet.
     *
     * @param Project $project
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validate(Project $project): bool
    {
        // Règle 1 : Le nom du projet est obligatoire
        if (empty($project->getName())) {
            throw new InvalidArgumentException('Le nom du projet est obligatoire.');
        }

        // Règle 2 : Le budget doit être positif
        if ($project->getBudget() <= 0) {
            throw new InvalidArgumentException('Le budget doit être positif.');
        }

        // Règle 3 (bonus) : La date de début doit précéder la date de fin
        $start = $project->getStartDate();
        $end   = $project->getEndDate();
        if ($start && $end && $end < $start) {
            throw new InvalidArgumentException('La date de fin doit être postérieure à la date de début.');
        }

        // Si toutes les règles sont respectées, on retourne true
        return true;
    }
}