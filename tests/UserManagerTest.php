<?php

namespace App\Tests;

use App\Entity\UserAccount;
use App\Service\UserManager;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    private UserManager $manager;

    protected function setUp(): void
    {
        $this->manager = new UserManager();
    }

    #[Test]
    public function validUser(): void
    {
        $user = new UserAccount();
        $user->setUsername('john_doe');
        $user->setEmail('john@example.com');
        $user->setRole('EMPLOYE');

        $this->assertTrue($this->manager->validate($user));
    }

    #[Test]
    public function userWithoutUsername(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom d\'utilisateur est obligatoire.');

        $user = new UserAccount();
        $user->setEmail('john@example.com');
        $user->setRole('EMPLOYE');
        $this->manager->validate($user);
    }

    #[Test]
    public function userWithInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'email n'est pas valide.");

        $user = new UserAccount();
        $user->setUsername('john_doe');
        $user->setEmail('invalid-email');
        $user->setRole('EMPLOYE');
        $this->manager->validate($user);
    }

    #[Test]
    public function userWithEmptyEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'email n'est pas valide.");

        $user = new UserAccount();
        $user->setUsername('john_doe');
        $user->setEmail('');
        $user->setRole('EMPLOYE');
        $this->manager->validate($user);
    }

    #[Test]
    public function userWithInvalidRole(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le rôle est invalide. Rôles autorisés : ADMINISTRATEUR, MANAGER, EMPLOYE');

        $user = new UserAccount();
        $user->setUsername('john_doe');
        $user->setEmail('john@example.com');
        $user->setRole('SUPER_ADMIN'); // rôle inexistant
        $this->manager->validate($user);
    }
}