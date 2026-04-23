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
        private MailerInterface $salaireMailer,   // Changé : nom spécifique
        private Environment $twig,
        private PdfService $pdfService,
    ) {}

    public function sendSalaireCreated(Salaire $salaire): void
    {
        $html = $this->twig->render('emails/salaire_created.html.twig', [
            'salaire' => $salaire,
        ]);

        $email = (new Email())
            ->from(new Address('rayenmsi2003@gmail.com', 'INTEGRA RH'))
            ->to($salaire->getUser()->getEmail())
            ->subject('🎉 Votre salaire a été créé - INTEGRA RH')
            ->html($html);

        $this->salaireMailer->send($email);
    }

    public function sendSalairePaid(Salaire $salaire): void
    {
        $html = $this->twig->render('emails/salaire_paye.html.twig', [
            'salaire' => $salaire,
        ]);

        $pdfContent = $this->pdfService->generatePayslipPdf($salaire);
        $fileName = $this->pdfService->getPayslipFileName($salaire);

        $email = (new Email())
            ->from(new Address('rayenmsi2003@gmail.com', 'INTEGRA RH'))
            ->to($salaire->getUser()->getEmail())
            ->subject('✅ Votre salaire a été payé - INTEGRA RH')
            ->html($html)
            ->attach($pdfContent, $fileName, 'application/pdf');

        $this->salaireMailer->send($email);
    }
}