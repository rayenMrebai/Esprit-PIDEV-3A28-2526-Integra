<?php
// src/Service/CertificatMailer.php

namespace App\Service;

use App\Entity\Quiz_result;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class CertificatMailer
{
    public function __construct(
        private MailerInterface $mailerInterface,
        private CertificatGenerator $certificatGenerator
    ) {}

    public function sendCertificat(Quiz_result $quiz): void
    {
        $user      = $quiz->getUser();
        $formation = $quiz->getTraining();
        $pdfContent = $this->certificatGenerator->generate($quiz);

        $fileName = sprintf(
            'certificat_%s_%s.pdf',
            $this->slugify($user->getUsername()),
            $this->slugify($formation->getTitle())
        );

        $email = (new Email())
            ->from('noreply@votre-plateforme.com')
            ->to($user->getEmail())
            ->subject('🎓 Félicitations ! Votre certificat pour ' . $formation->getTitle())
            ->html($this->buildEmailHtml($user->getUsername(), $formation->getTitle(), $quiz->getPercentage()))
            ->addPart(
                new DataPart($pdfContent, $fileName, 'application/pdf')
            );

        $this->mailerInterface->send($email);
    }

    private function buildEmailHtml(string $userName, string $formationTitle, float $percentage): string
    {
        return sprintf('
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px;">
            <div style="background: #1a3c6e; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
                <h1 style="color: #c9a84c; margin: 0;">🎓 Félicitations !</h1>
            </div>
            <div style="background: #f9f9f9; padding: 30px; border: 1px solid #ddd;">
                <p style="font-size: 16px;">Bonjour <strong>%s</strong>,</p>
                <p>Vous avez réussi le quiz de la formation <strong>%s</strong> avec un score de <strong>%.0f%%</strong>.</p>
                <p>Votre certificat de réussite est joint à cet email en PDF.</p>
                <div style="text-align: center; margin: 30px 0;">
                    <span style="background: #1a3c6e; color: white; padding: 12px 30px; border-radius: 5px; font-size: 16px;">
                        📄 Certificat en pièce jointe
                    </span>
                </div>
                <p style="color: #777; font-size: 13px;">
                    Ce certificat atteste de votre maîtrise du sujet et peut être partagé sur votre profil professionnel.
                </p>
            </div>
            <div style="background: #eee; padding: 10px; text-align: center; font-size: 12px; color: #999; border-radius: 0 0 8px 8px;">
                Plateforme de Formation Professionnelle
            </div>
        </div>',
            htmlspecialchars($userName),
            htmlspecialchars($formationTitle),
            $percentage
        );
    }

    private function slugify(string $text): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $text), '_'));
    }
}