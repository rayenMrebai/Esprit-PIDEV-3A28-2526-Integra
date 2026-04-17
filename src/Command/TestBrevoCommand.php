<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'app:test-brevo')]
class TestBrevoCommand extends Command
{
    public function __construct(private MailerInterface $mailer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (new Email())
            ->from('rayenmsi2003@gmail.com')
            ->to('rayenmsi2003@gmail.com')
            ->subject('✅ Test Brevo - Système Salaires')
            ->html('
                <h2>Brevo fonctionne !</h2>
                <p>Ton système de notification de salaires est prêt 🎉</p>
            ');

        $this->mailer->send($email);

        $output->writeln('✅ Email envoyé via Brevo ! Vérifie ta boîte Gmail.');

        return Command::SUCCESS;
    }
}