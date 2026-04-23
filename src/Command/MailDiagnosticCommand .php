<?php
// src/Command/MailDiagnosticCommand.php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Transport\TransportInterface;

class MailDiagnosticCommand extends Command
{
    protected static $defaultName = 'mail:diagnostic';

    public function __construct(
        private MailerInterface $mailer,
        private TransportInterface $transport
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('=== DIAGNOSTIC MAILER ===');
        
        // 1. Vérifier le DSN
        $output->writeln('1. Configuration actuelle:');
        $output->writeln('   DSN: ' . ($_ENV['MAILER_DSN'] ?? 'Non défini'));
        
        // 2. Tester la connexion SMTP
        $output->writeln("\n2. Test de connexion SMTP...");
        
        // 3. Tester l'envoi simple
        $output->writeln("\n3. Test d'envoi simple...");
        
        $email = (new Email())
            ->from('sarabeji123@gmail.com')
            ->to('sarabeji123@gmail.com') // Envoyer à vous-même
            ->subject('Diagnostic Symfony Mailer - ' . date('H:i:s'))
            ->text('Test de diagnostic');
        
        try {
            $this->mailer->send($email);
            $output->writeln("   ✅ Email envoyé avec succès !");
            $output->writeln("   📨 Vérifiez votre boîte de réception (et les spams)");
        } catch (\Exception $e) {
            $output->writeln("   ❌ Erreur: " . $e->getMessage());
            $output->writeln("   📋 Type: " . get_class($e));
            $output->writeln("   📍 Fichier: " . $e->getFile() . ":" . $e->getLine());
        }
        
        return Command::SUCCESS;
    }
}