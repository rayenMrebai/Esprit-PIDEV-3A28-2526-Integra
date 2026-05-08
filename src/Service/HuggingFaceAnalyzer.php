<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HuggingFaceAnalyzer
{
    private HttpClientInterface $httpClient;
    private string $token;
    private string $url;
    private string $model;
    private LoggerInterface $logger;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger, string $token, string $url, string $model)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->token = $token;
        $this->url = $url;
        $this->model = $model;
    }

    /**
     * Appelle l'API Hugging Face (routeur OpenAI-compatible) pour analyser un CV.
     * @return array<string, string>
     */
    public function analyzeCV(string $cvText, string $jobDescription): array
    {
        $systemMessage = [
            'role' => 'system',
            'content' => "Tu es un assistant RH expert. Tu réponds TOUJOURS et UNIQUEMENT avec un objet JSON valide. Aucun texte avant ou après le JSON. Jamais de markdown."
        ];

        $userContent = "Analyse ce CV par rapport à cette offre d'emploi.\n\n" .
            "=== OFFRE D'EMPLOI ===\n" . $this->truncate($jobDescription, 600) . "\n\n" .
            "=== CV DU CANDIDAT ===\n" . $this->truncate($cvText, 1800) . "\n\n" .
            "Retourne UNIQUEMENT ce JSON :\n" .
            "{\n" .
            "  \"firstName\": \"prénom extrait du CV\",\n" .
            "  \"lastName\": \"nom extrait du CV\",\n" .
            "  \"email\": \"email extrait du CV\",\n" .
            "  \"phone\": \"téléphone extrait du CV\",\n" .
            "  \"educationLevel\": \"niveau d'études\",\n" .
            "  \"skills\": \"compétences séparées par virgules\",\n" .
            "  \"jobTitle\": \"poste visé\",\n" .
            "  \"recommendation\": \"garder ou rejeter\",\n" .
            "  \"reason\": \"justification courte\"\n" .
            "}\n\n" .
            "IMPORTANT: recommendation = exactement 'garder' ou 'rejeter'.";

        $messages = [$systemMessage, ['role' => 'user', 'content' => $userContent]];

        try {
            $response = $this->httpClient->request('POST', $this->url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => $this->model,
                    'messages'    => $messages,
                    'max_tokens'  => 600,
                    'temperature' => 0.1,
                    'stream'      => false,
                ],
                'timeout' => 120,
            ]);

            $responseData = $response->toArray(false);
            $this->logger->info('HuggingFace API response', ['status' => $response->getStatusCode()]);

            if ($response->getStatusCode() !== 200) {
                $errorMsg = $responseData['error']['message'] ?? 'Erreur HTTP ' . $response->getStatusCode();
                return $this->errorResult($errorMsg);
            }

            $content = $responseData['choices'][0]['message']['content'] ?? '';
            $jsonData = $this->extractJson($content);
            if (empty($jsonData)) {
                return $this->fallbackResult($content);
            }

            $data = json_decode($jsonData, true);
            return [
                'firstName' => $data['firstName'] ?? '',
                'lastName' => $data['lastName'] ?? '',
                'email' => $data['email'] ?? '',
                'phone' => $data['phone'] ?? '',
                'educationLevel' => $data['educationLevel'] ?? '',
                'skills' => $data['skills'] ?? '',
                'jobTitle' => $data['jobTitle'] ?? '',
                'recommendation' => $data['recommendation'] ?? '',
                'reason' => $data['reason'] ?? '',
            ];
        } catch (\Exception $e) {
            $this->logger->error('HuggingFace API exception', ['message' => $e->getMessage()]);
            return $this->errorResult($e->getMessage());
        }
    }

    public function generateRejectionLetter(string $firstName, string $lastName, string $jobTitle, string $reason): string
    {
        $systemMessage = [
            'role' => 'system',
            'content' => "Tu es un responsable RH professionnel. Rédige une lettre de refus courte et empathique en français. Réponds UNIQUEMENT avec le texte de la lettre."
        ];

        $userContent = sprintf(
            "Rédige une lettre de refus pour %s %s (poste : %s). Raison (interne) : %s. Sois bienveillant.",
            $firstName, $lastName, $jobTitle, $reason
        );

        try {
            $response = $this->httpClient->request('POST', $this->url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => $this->model,
                    'messages'    => [$systemMessage, ['role' => 'user', 'content' => $userContent]],
                    'max_tokens'  => 500,
                    'temperature' => 0.7,
                    'stream'      => false,
                ],
                'timeout' => 60,
            ]);

            $data = $response->toArray(false);
            return trim($data['choices'][0]['message']['content'] ?? '');
        } catch (\Exception $e) {
            $this->logger->error('Rejection letter generation failed', ['error' => $e->getMessage()]);
            return sprintf(
                "Bonjour %s %s,\n\nMerci pour votre candidature. Après étude, nous ne pouvons y donner suite.\n\nCordialement,\nL'équipe INTEGRA",
                $firstName, $lastName
            );
        }
    }

    private function extractJson(string $text): string
    {
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $text, $matches)) return $matches[1];
        if (preg_match('/\{.*\}/s', $text, $matches)) return $matches[0];
        return '';
    }

    /** @return array<string, string> */
    private function fallbackResult(string $rawText): array
    {
        $rec = (stripos($rawText, 'garder') !== false) ? 'garder' : ((stripos($rawText, 'rejeter') !== false) ? 'rejeter' : 'indéterminé');
        return [
            'firstName' => '', 'lastName' => '', 'email' => '', 'phone' => '', 'educationLevel' => '', 'skills' => '', 'jobTitle' => '',
            'recommendation' => $rec,
            'reason' => 'Analyse partielle : ' . $this->truncate($rawText, 200)
        ];
    }

    /** @return array<string, string> */
    private function errorResult(string $reason): array
    {
        return [
            'firstName' => '', 'lastName' => '', 'email' => '', 'phone' => '', 'educationLevel' => '', 'skills' => '', 'jobTitle' => '',
            'recommendation' => 'erreur',
            'reason' => $reason
        ];
    }

    private function truncate(string $text, int $max): string
    {
        return (strlen($text) <= $max) ? $text : substr($text, 0, $max) . '...';
    }
}