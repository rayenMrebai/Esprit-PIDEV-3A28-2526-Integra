<?php

namespace App\Tests\Service;

use App\Entity\Salaire;
use App\Service\SalaireManager;
use PHPUnit\Framework\TestCase;

class SalaireManagerTest extends TestCase
{
    // salaire valide
    public function testValidSalaire(): void
    {
        $salaire = new Salaire();
        $salaire->setBaseAmount(1500);
        $salaire->setDatePaiement(new \DateTime('tomorrow'));
        $salaire->setStatus('CREÉ');

        $manager = new SalaireManager();

        $this->assertTrue($manager->validate($salaire));
    }

    // base amount null
    public function testSalaireWithZeroBaseAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le salaire de base doit être supérieur à zéro.');

        $salaire = new Salaire();
        $salaire->setBaseAmount(0);
        $salaire->setDatePaiement(new \DateTime('tomorrow'));
        $salaire->setStatus('CREÉ');

        $manager = new SalaireManager();
        $manager->validate($salaire);
    }


    // base amount negative
    public function testSalaireWithNegativeBaseAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le salaire de base doit être supérieur à zéro.');

        $salaire = new Salaire();
        $salaire->setBaseAmount(-500);
        $salaire->setDatePaiement(new \DateTime('tomorrow'));
        $salaire->setStatus('CREÉ');

        $manager = new SalaireManager();
        $manager->validate($salaire);
    }

    //date hier
    public function testSalaireWithPastDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de paiement ne peut pas être dans le passé.');

        $salaire = new Salaire();
        $salaire->setBaseAmount(1500);
        $salaire->setDatePaiement(new \DateTime('yesterday'));
        $salaire->setStatus('CREÉ');

        $manager = new SalaireManager();
        $manager->validate($salaire);
    }

    //date null
    public function testSalaireWithoutDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de paiement est obligatoire.');

        $salaire = new Salaire();
        $salaire->setBaseAmount(1500);
        $salaire->setStatus('CREÉ');
        // datePaiement reste null

        $manager = new SalaireManager();
        $manager->validate($salaire);
    }

    // date today
    public function testSalaireWithTodayDate(): void
    {
        $salaire = new Salaire();
        $salaire->setBaseAmount(1500);
        $salaire->setDatePaiement(new \DateTime('today'));
        $salaire->setStatus('CREÉ');

        $manager = new SalaireManager();

        $this->assertTrue($manager->validate($salaire));
    }
    
    //statut invalide
    public function testSalaireWithInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut doit être : CREÉ, EN_COURS ou PAYÉ.');

        $salaire = new Salaire();
        $salaire->setBaseAmount(1500);
        $salaire->setDatePaiement(new \DateTime('tomorrow'));
        $salaire->setStatus('INVALIDE'); // statut invalide

        $manager = new SalaireManager();
        $manager->validate($salaire);
    }

    // statut en cours
    public function testSalaireWithStatusEnCours(): void
    {
        $salaire = new Salaire();
        $salaire->setBaseAmount(1500);
        $salaire->setDatePaiement(new \DateTime('tomorrow'));
        $salaire->setStatus('EN_COURS');

        $manager = new SalaireManager();

        $this->assertTrue($manager->validate($salaire));
    }
}