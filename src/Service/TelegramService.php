<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class TelegramService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $botToken;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger, string $telegramBotToken)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->botToken = $telegramBotToken;
    }

    public function sendMessage(string $chatId, string $message): bool
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        try {
            $response = $this->httpClient->request('POST', $url, [
                'json' => [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $this->logger->info('Message Telegram envoyé avec succès à ' . $chatId);
                return true;
            }

            $this->logger->error('Erreur Telegram : ' . $response->getContent(false));
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Exception Telegram : ' . $e->getMessage());
            return false;
        }
    }
}