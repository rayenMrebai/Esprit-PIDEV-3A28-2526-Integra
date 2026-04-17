<?php

namespace App\Service;

use App\Entity\Salaire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailService
{
    private const SENDER = 'rayenmsi2003@gmail.com';
    private const SENDER_NAME = 'INTEGRA RH';

    public function __construct(
        private MailerInterface $mailer,
        private Environment     $twig,
        private PdfService      $pdfService
    ) {}

    // ─────────────────────────────────────────────
    // 1. Email : Salaire créé
    // ─────────────────────────────────────────────
    public function sendSalaireCreated(Salaire $salaire): void
    {
        $html = $this->twig->render('emails/salaire_created.html.twig', [
            'salaire' => $salaire,
        ]);

        $email = (new Email())
            ->from(new Address(self::SENDER, self::SENDER_NAME))
            ->replyTo(new Address(self::SENDER, self::SENDER_NAME))
            ->to($salaire->getUser()->getEmail())
            ->subject('🎉 Votre salaire a été créé - INTEGRA RH')
            ->html($html);

        $this->mailer->send($email);
    }

    // ─────────────────────────────────────────────
    // 2. Email : Salaire payé + fiche PDF en pièce jointe
    // ─────────────────────────────────────────────
    public function sendSalairePaid(Salaire $salaire): void
    {
        $html = $this->twig->render('emails/salaire_paye.html.twig', [
            'salaire' => $salaire,
        ]);

        $pdfContent = $this->pdfService->generatePayslipPdf($salaire);
        $fileName   = $this->pdfService->getPayslipFileName($salaire);

        $email = (new Email())
            ->from(new Address(self::SENDER, self::SENDER_NAME))
            ->replyTo(new Address(self::SENDER, self::SENDER_NAME))
            ->to($salaire->getUser()->getEmail())
            ->subject('✅ Votre salaire a été payé - INTEGRA RH')
            ->html($html)
            ->attach($pdfContent, $fileName, 'application/pdf');

        $this->mailer->send($email);
    }
}