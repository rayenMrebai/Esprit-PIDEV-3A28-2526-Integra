<?php

namespace App\Tests\Service;

use App\Entity\Training_program;
use App\Service\TrainingProgramManager;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use DateTime;

class TrainingProgramManagerTest extends TestCase
{
    private TrainingProgramManager $trainingProgramManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->trainingProgramManager = new TrainingProgramManager();
    }

    /**
     * Test 1: Vérifie qu'un programme valide est accepté
     */
    public function testValidTrainingProgram(): void
    {
        $trainingProgram = new Training_program();
        $trainingProgram->setTitle('Formation Symfony');
        $trainingProgram->setDuration(5);
        $trainingProgram->setStartDate(new DateTime('2025-01-01'));
        $trainingProgram->setEndDate(new DateTime('2025-01-10'));

        $result = $this->trainingProgramManager->validate($trainingProgram);

        $this->assertTrue($result);
    }

    /**
     * Test 2: Vérifie que la date de fin postérieure à la date de début lève une exception
     */
    public function testTrainingProgramWithEndDateBeforeStartDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début.');

        $trainingProgram = new Training_program();
        $trainingProgram->setStartDate(new DateTime('2025-01-10'));
        $trainingProgram->setEndDate(new DateTime('2025-01-01'));

        $this->trainingProgramManager->validate($trainingProgram);
    }

    /**
     * Test 3: Vérifie que des dates égales lèvent une exception
     */
    public function testTrainingProgramWithEqualDates(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début.');

        $trainingProgram = new Training_program();
        $trainingProgram->setStartDate(new DateTime('2025-01-01'));
        $trainingProgram->setEndDate(new DateTime('2025-01-01'));

        $this->trainingProgramManager->validate($trainingProgram);
    }

    /**
     * Test 4: Vérifie qu'une durée négative lève une exception
     */
    public function testTrainingProgramWithNegativeDuration(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La durée doit être un nombre positif.');

        $trainingProgram = new Training_program();
        $trainingProgram->setDuration(-5);
        $trainingProgram->setStartDate(new DateTime('2025-01-01'));
        $trainingProgram->setEndDate(new DateTime('2025-01-10'));

        $this->trainingProgramManager->validate($trainingProgram);
    }

    /**
     * Test 5: Vérifie qu'une durée nulle lève une exception
     */
    public function testTrainingProgramWithZeroDuration(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La durée doit être un nombre positif.');

        $trainingProgram = new Training_program();
        $trainingProgram->setDuration(0);
        $trainingProgram->setStartDate(new DateTime('2025-01-01'));
        $trainingProgram->setEndDate(new DateTime('2025-01-10'));

        $this->trainingProgramManager->validate($trainingProgram);
    }

    /**
     * Test 6: Vérifie la méthode isCompleted - programme terminé
     */
    public function testIsCompletedTrue(): void
    {
        $trainingProgram = new Training_program();
        $trainingProgram->setStatus('TERMINÉ');

        $result = $this->trainingProgramManager->isCompleted($trainingProgram);
        
        $this->assertTrue($result);
    }

    /**
     * Test 7: Vérifie la méthode isCompleted - programme non terminé
     */
    public function testIsCompletedFalse(): void
    {
        $trainingProgram = new Training_program();
        $trainingProgram->setStatus('PROGRAMMÉ');

        $result = $this->trainingProgramManager->isCompleted($trainingProgram);
        
        $this->assertFalse($result);
    }

    /**
     * Test 8: Vérifie la méthode isAvailable - programme disponible
     */
    public function testIsAvailableTrue(): void
    {
        $trainingProgram = new Training_program();
        $trainingProgram->setStatus('PROGRAMMÉ');

        $result = $this->trainingProgramManager->isAvailable($trainingProgram);
        
        $this->assertTrue($result);
    }

    /**
     * Test 9: Vérifie la méthode isAvailable - programme non disponible
     */
    public function testIsAvailableFalse(): void
    {
        $trainingProgram = new Training_program();
        $trainingProgram->setStatus('TERMINÉ');

        $result = $this->trainingProgramManager->isAvailable($trainingProgram);
        
        $this->assertFalse($result);
    }

    /**
     * Test 10: Vérifie que null dates are accepted (pas de validation de dates)
     */
    public function testTrainingProgramWithNullDates(): void
    {
        $trainingProgram = new Training_program();
        $trainingProgram->setDuration(5);
        $trainingProgram->setStartDate(null);
        $trainingProgram->setEndDate(null);

        $result = $this->trainingProgramManager->validate($trainingProgram);

        $this->assertTrue($result);
    }
}