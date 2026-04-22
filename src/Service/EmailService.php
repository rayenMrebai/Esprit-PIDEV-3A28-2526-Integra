<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    private $mailer;
    private $fromEmail;

    public function __construct(MailerInterface $mailer, string $fromEmail)
    {
        $this->mailer = $mailer;
        $this->fromEmail = $fromEmail;
    }

    public function sendRejectionLetter(string $toEmail, string $toName, string $jobTitle, string $letterContent): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('Suite à votre candidature - ' . $jobTitle)
            ->html($letterContent)
            ->text(strip_tags($letterContent));

        $this->mailer->send($email);
    }
}