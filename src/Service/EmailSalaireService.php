<?php

namespace App\Service;

use App\Entity\Salaire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailSalaireService
{
    public function __construct(
        private MailerInterface $mailer,   // = mailer.salaire (Brevo)
        private Environment $twig,
        private PdfService $pdfService,
    ) {}

    public function sendSalaireCreated(Salaire $salaire): void
    {
        $user = $salaire->getUser();
        if ($user === null) {
            throw new \RuntimeException('Utilisateur non trouvé pour ce salaire');
        }

        $emailAddress = $user->getEmail();
        if ($emailAddress === null) {
            throw new \RuntimeException('Email de l\'utilisateur non défini');
        }

        $html = $this->twig->render('emails/salaire_created.html.twig', [
            'salaire' => $salaire,
        ]);

        $email = (new Email())
            ->from(new Address('rayenmsi2003@gmail.com', 'INTEGRA RH'))
            ->to($emailAddress)
            ->subject('🎉 Votre salaire a été créé - INTEGRA RH')
            ->html($html);

        $this->mailer->send($email);
    }

    public function sendSalairePaid(Salaire $salaire): void
    {
        $user = $salaire->getUser();
        if ($user === null) {
            throw new \RuntimeException('Utilisateur non trouvé pour ce salaire');
        }

        $emailAddress = $user->getEmail();
        if ($emailAddress === null) {
            throw new \RuntimeException('Email de l\'utilisateur non défini');
        }
        
        $html = $this->twig->render('emails/salaire_paye.html.twig', [
            'salaire' => $salaire,
        ]);

        $pdfContent = $this->pdfService->generatePayslipPdf($salaire);
        $fileName = $this->pdfService->getPayslipFileName($salaire);

        $email = (new Email())
            ->from(new Address('rayenmsi2003@gmail.com', 'INTEGRA RH'))
            ->to($emailAddress)
            ->subject('✅ Votre salaire a été payé - INTEGRA RH')
            ->html($html)
            ->attach($pdfContent, $fileName, 'application/pdf');

        $this->mailer->send($email);
    }
}