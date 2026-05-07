<?php

namespace App\Tests;

use App\Entity\Project;
use App\Service\ProjectManager;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ProjectManagerTest extends TestCase
{
    private ProjectManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ProjectManager();
    }

    #[Test]
    public function validProject(): void
    {
        $project = new Project();
        $project->setName('Projet Test');
        $project->setBudget(1000);
        $project->setStartDate(new \DateTime('2026-01-01'));
        $project->setEndDate(new \DateTime('2026-12-31'));

        $this->assertTrue($this->manager->validate($project));
    }

    #[Test]
    public function projectWithoutName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du projet est obligatoire.');

        $project = new Project();
        $project->setBudget(1000);
        $this->manager->validate($project);
    }

    #[Test]
    public function projectWithNegativeBudget(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le budget doit être positif.');

        $project = new Project();
        $project->setName('Projet Test');
        $project->setBudget(-50);
        $this->manager->validate($project);
    }

    #[Test]
    public function projectWithInvalidDates(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début.');

        $project = new Project();
        $project->setName('Projet Test');
        $project->setBudget(500);
        $project->setStartDate(new \DateTime('2026-12-31'));
        $project->setEndDate(new \DateTime('2026-01-01'));
        $this->manager->validate($project);
    }
}