<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Quiz_result;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CertificatMailer
{
    public function __construct(
        private MailerInterface $certificatMailer,
        private CertificatGenerator $certificatGenerator,
        private LoggerInterface $logger,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function sendCertificat(Quiz_result $quiz): void
    {
        // Correction : utiliser $quiz (pas $quizResult)
        $user = $quiz->getUser();

        if (!$user) {
            throw new \RuntimeException('Utilisateur introuvable');
        }
        $formation = $quiz->getTraining();
        if (!$formation) {
            return;
        }

        $this->logger->info('Envoi certificat', [
            'user_email' => $user->getEmail(),
            'formation'  => $formation->getTitle(),
            'score'      => $quiz->getPercentage(),
        ]);

        // Générer le PDF
        $pdfContent = $this->certificatGenerator->generate($quiz);

        // Sécurisation : valeurs string/null -> string
        $userName = (string) $user->getUsername();
        $formationTitle = (string) $formation->getTitle();
        $percentage = $quiz->getPercentage() ?? 0.0;

        $fileName = sprintf(
            'certificat_%s_%s.pdf',
            $this->slugify($userName),
            $this->slugify($formationTitle)
        );

        // Générer le lien de téléchargement
        $certificatUrl = $this->urlGenerator->generate(
            'app_frontoffice_quiz_certificat',
            ['id' => $quiz->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Construire et envoyer l'email
        $email = (new Email())
            ->from('sarabeji123@gmail.com')
            ->to($user->getEmail())
            ->subject('Félicitations ! Votre certificat pour ' . $formationTitle)
            ->html($this->buildEmailHtml(
                $userName,
                $formationTitle,
                $percentage,
                $certificatUrl
            ))
            ->addPart(new DataPart($pdfContent, $fileName, 'application/pdf'));

        $this->certificatMailer->send($email);

        $this->logger->info('Certificat envoyé avec succès à ' . $user->getEmail());
    }

    private function buildEmailHtml(
        string $userName,
        string $formationTitle,
        float $percentage,
        string $certificatUrl
    ): string {
        return sprintf('
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px;">
            <div style="background: #1a3c6e; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
                <h1 style="color: #c9a84c; margin: 0;">Félicitations !</h1>
            </div>
            <div style="background: #f9f9f9; padding: 30px; border: 1px solid #ddd;">
                <p style="font-size: 16px;">Bonjour <strong>%s</strong>,</p>
                <p>Vous avez réussi le quiz de la formation <strong>%s</strong>
                   avec un score de <strong>%.0f%%</strong>.</p>
                <p>Votre certificat est disponible de deux façons :</p>

                <div style="text-align: center; margin: 30px 0;">
                    <a href="%s"
                       style="background: #c9a84c; color: white; padding: 14px 35px;
                              border-radius: 5px; font-size: 16px; text-decoration: none;
                              display: inline-block;">
                        📄 Télécharger mon certificat
                    </a>
                </div>

                <p style="color: #777; font-size: 13px; text-align: center;">
                    Le PDF est également joint à cet email.
                </p>
            </div>
            <div style="background: #eee; padding: 10px; text-align: center;
                        font-size: 12px; color: #999; border-radius: 0 0 8px 8px;">
                Plateforme de Formation Professionnelle
            </div>
        </div>',
            htmlspecialchars($userName),
            htmlspecialchars($formationTitle),
            $percentage,
            htmlspecialchars($certificatUrl)
        );
    }

    private function slugify(string $text): string
    {
        // $text est toujours string, pas de null ici car les appels sont castés
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $text), '_'));
    }
}