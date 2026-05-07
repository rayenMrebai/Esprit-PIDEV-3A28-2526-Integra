<?php

namespace App\Tests;

use App\Entity\Project;
use App\Entity\Projectassignment;
use App\Entity\UserAccount;
use App\Service\AssignmentManager;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AssignmentManagerTest extends TestCase
{
    private AssignmentManager $manager;

    protected function setUp(): void
    {
        $this->manager = new AssignmentManager();
    }

    #[Test]
    public function validAssignment(): void
    {
        $project = new Project();
        $project->setName('Projet Test');
        $project->setBudget(1000);

        $user = new UserAccount();
        $user->setUsername('testuser');

        $assignment = new Projectassignment();
        $assignment->setProject($project);
        $assignment->setUserAccount($user);
        $assignment->setRole('Développeur');
        $assignment->setAllocationRate(80);

        $this->assertTrue($this->manager->validate($assignment));
    }

    #[Test]
    public function assignmentWithoutRole(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le rôle est obligatoire.');

        $assignment = new Projectassignment();
        $assignment->setAllocationRate(50);
        $this->manager->validate($assignment);
    }

    #[Test]
    public function assignmentWithInvalidAllocation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Le taux d'allocation doit être compris entre 0 et 100.");

        $assignment = new Projectassignment();
        $assignment->setRole('Développeur');
        $assignment->setAllocationRate(120);
        $this->manager->validate($assignment);
    }

    #[Test]
    public function assignmentWithoutProject(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le projet est obligatoire.');

        $assignment = new Projectassignment();
        $assignment->setRole('Développeur');
        $assignment->setAllocationRate(50);
        $this->manager->validate($assignment);
    }

    #[Test]
    public function assignmentWithoutEmployee(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'employé est obligatoire.");

        $assignment = new Projectassignment();
        $assignment->setRole('Développeur');
        $assignment->setAllocationRate(50);
        $assignment->setProject(new Project());
        $this->manager->validate($assignment);
    }
}