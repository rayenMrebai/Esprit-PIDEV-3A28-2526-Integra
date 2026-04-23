<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    public function __construct(
        private MailerInterface $recrutementMailer  // Changé : nom spécifique
    ) {}

    public function sendRecruitmentEmail(string $to, string $subject, string $htmlContent): void
    {
        $email = (new Email())
            ->from('walabentahar0@gmail.com')
            ->to($to)
            ->subject($subject)
            ->html($htmlContent);

        $this->recrutementMailer->send($email);
    }
}