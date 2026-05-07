<?php

namespace App\Tests\Service;

use App\Entity\Jobposition;
use App\Service\JobpositionValidator;
use PHPUnit\Framework\TestCase;

class JobpositionValidatorTest extends TestCase
{
    private JobpositionValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new JobpositionValidator();
    }

    public function testValidJobposition(): void
    {
        $job = new Jobposition();
        $job->setTitle('Développeur Symfony');
        $job->setDepartement('IT');
        $job->setEmployeeType('CDI');
        $job->setDescription('Nous recherchons un développeur Symfony expérimenté.');
        $job->setPostedAt(new \DateTime('2025-05-07'));
        $job->setStatus('Open');

        $this->assertTrue($this->validator->validate($job));
    }

    public function testJobpositionWithoutTitle(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre de l\'offre est obligatoire.');

        $job = new Jobposition();
        $job->setDepartement('IT');
        $job->setEmployeeType('CDI');
        $job->setDescription('Description valide suffisamment longue.');
        $job->setPostedAt(new \DateTime());
        $job->setStatus('Open');

        $this->validator->validate($job);
    }

    public function testJobpositionWithoutDepartement(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le département est obligatoire.');

        $job = new Jobposition();
        $job->setTitle('Développeur');
        $job->setEmployeeType('CDI');
        $job->setDescription('Description valide suffisamment longue.');
        $job->setPostedAt(new \DateTime());
        $job->setStatus('Open');

        $this->validator->validate($job);
    }

    public function testJobpositionWithoutEmployeeType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le type d\'emploi est obligatoire.');

        $job = new Jobposition();
        $job->setTitle('Développeur');
        $job->setDepartement('IT');
        $job->setDescription('Description valide suffisamment longue.');
        $job->setPostedAt(new \DateTime());
        $job->setStatus('Open');

        $this->validator->validate($job);
    }

    public function testJobpositionWithShortDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description doit contenir au moins 10 caractères.');

        $job = new Jobposition();
        $job->setTitle('Développeur');
        $job->setDepartement('IT');
        $job->setEmployeeType('CDI');
        $job->setDescription('Courte');
        $job->setPostedAt(new \DateTime());
        $job->setStatus('Open');

        $this->validator->validate($job);
    }

    public function testJobpositionWithoutPostedAt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de publication est obligatoire.');

        $job = new Jobposition();
        $job->setTitle('Développeur');
        $job->setDepartement('IT');
        $job->setEmployeeType('CDI');
        $job->setDescription('Description valide suffisamment longue.');
        $job->setStatus('Open');

        $this->validator->validate($job);
    }

    public function testJobpositionWithInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut doit être "Open" ou "Closed".');

        $job = new Jobposition();
        $job->setTitle('Développeur');
        $job->setDepartement('IT');
        $job->setEmployeeType('CDI');
        $job->setDescription('Description valide suffisamment longue.');
        $job->setPostedAt(new \DateTime());
        $job->setStatus('En pause');

        $this->validator->validate($job);
    }
}