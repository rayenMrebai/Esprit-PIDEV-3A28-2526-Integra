<?php

namespace App\Tests\Service;

use App\Entity\Candidat;
use App\Service\CandidatValidator;
use PHPUnit\Framework\TestCase;

class CandidatValidatorTest extends TestCase
{
    private CandidatValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new CandidatValidator();
    }

    public function testValidCandidat(): void
    {
        $candidat = new Candidat();
        $candidat->setFirstName('Jean');
        $candidat->setLastName('Dupont');
        $candidat->setEmail('jean.dupont@example.com');
        $candidat->setPhone(123456789);
        $candidat->setStatus('Nouveau');

        $this->assertTrue($this->validator->validate($candidat));
    }

    public function testCandidatWithoutFirstName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prénom du candidat est obligatoire.');

        $candidat = new Candidat();
        $candidat->setLastName('Dupont');
        $candidat->setEmail('jean.dupont@example.com');
        $candidat->setStatus('Nouveau');

        $this->validator->validate($candidat);
    }

    public function testCandidatWithoutLastName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du candidat est obligatoire.');

        $candidat = new Candidat();
        $candidat->setFirstName('Jean');
        $candidat->setEmail('jean.dupont@example.com');
        $candidat->setStatus('Nouveau');

        $this->validator->validate($candidat);
    }

    public function testCandidatWithInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'email du candidat doit être valide.');

        $candidat = new Candidat();
        $candidat->setFirstName('Jean');
        $candidat->setLastName('Dupont');
        $candidat->setEmail('email_invalide');
        $candidat->setStatus('Nouveau');

        $this->validator->validate($candidat);
    }

    public function testCandidatWithInvalidPhone(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le téléphone doit être un nombre valide.');

        $candidat = new Candidat();
        $candidat->setFirstName('Jean');
        $candidat->setLastName('Dupont');
        $candidat->setEmail('jean.dupont@example.com');
        $candidat->setPhone(-5); // nombre négatif
        $candidat->setStatus('Nouveau');

        $this->validator->validate($candidat);
    }

    public function testCandidatWithInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut du candidat n\'est pas valide.');

        $candidat = new Candidat();
        $candidat->setFirstName('Jean');
        $candidat->setLastName('Dupont');
        $candidat->setEmail('jean.dupont@example.com');
        $candidat->setStatus('StatutInvalide');

        $this->validator->validate($candidat);
    }
}