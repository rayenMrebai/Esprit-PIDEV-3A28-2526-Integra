<?php

namespace App\Service;

use App\Entity\Training_program;
use InvalidArgumentException;

class TrainingProgramManager
{
    /**
     * Valide les règles métier pour l'entité Training_program
     * Règles:
     * 1. La date de fin doit être postérieure à la date de début
     * 2. La durée doit être positive
     * 
     * @param Training_program $trainingProgram
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validate(Training_program $trainingProgram): bool
    {
        // Règle 1: La date de fin doit être postérieure à la date de début
        $startDate = $trainingProgram->getStartDate();
        $endDate = $trainingProgram->getEndDate();
        
        if ($startDate !== null && $endDate !== null) {
            if ($endDate <= $startDate) {
                throw new InvalidArgumentException(
                    'La date de fin doit être postérieure à la date de début.'
                );
            }
        }

        // Règle 2: La durée doit être positive
        $duration = $trainingProgram->getDuration();
        if ($duration !== null && $duration <= 0) {
            throw new InvalidArgumentException(
                'La durée doit être un nombre positif.'
            );
        }

        return true;
    }

    /**
     * Vérifie si le programme est terminé
     * 
     * @param Training_program $trainingProgram
     * @return bool
     */
    public function isCompleted(Training_program $trainingProgram): bool
    {
        return $trainingProgram->getStatus() === 'TERMINÉ';
    }

    /**
     * Vérifie si le programme est disponible (pas commencé ou en cours)
     * 
     * @param Training_program $trainingProgram
     * @return bool
     */
    public function isAvailable(Training_program $trainingProgram): bool
    {
        $status = $trainingProgram->getStatus();
        return $status === 'PROGRAMMÉ' || $status === 'EN COURS';
    }
}