<?php

namespace App\Command;

use App\Entity\UserAccount;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin')]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $existing = $this->em->getRepository(UserAccount::class)
            ->findOneBy(['email' => 'admin@admin.com']);
        if ($existing) {
            $this->em->remove($existing);
            $this->em->flush();
        }

        $user = new UserAccount();
        $user->setUsername('admin');
        $user->setEmail('admin@admin.com');
        $user->setRole('ADMINISTRATEUR');
        $user->setIsActive(true);
        $user->setAccountStatus('ACTIVE');
        $user->setPasswordHash(
            $this->hasher->hashPassword($user, 'password123')
        );

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln('Admin créé : admin@admin.com / password123');
        return Command::SUCCESS;
    }
}