<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HuggingFaceAnalyzer
{
    private $httpClient;
    private $token;
    private $url;
    private $model;
    private $logger;

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
     * Retourne un tableau avec les clés : firstName, lastName, email, phone, educationLevel, skills, jobTitle, recommendation, reason.
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

        $userMessage = [
            'role' => 'user',
            'content' => $userContent
        ];

        $messages = [$systemMessage, $userMessage];

        $body = [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => 600,
            'temperature' => 0.1,
            'stream' => false,
        ];

        try {
            $response = $this->httpClient->request('POST', $this->url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
                'timeout' => 120,
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = $response->toArray(false);

            $this->logger->info('HuggingFace API response', ['status' => $statusCode, 'body' => $responseData]);

            if ($statusCode !== 200) {
                $errorMsg = $responseData['error']['message'] ?? 'Erreur HTTP ' . $statusCode;
                return $this->errorResult($errorMsg);
            }

            // Extraire le contenu du message
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

    private function extractJson(string $text): string
    {
        // Supprime les blocs markdown ```json ... ```
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $text, $matches)) {
            return $matches[1];
        }
        // Cherche le premier objet JSON
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            return $matches[0];
        }
        return '';
    }

    private function fallbackResult(string $rawText): array
    {
        $rec = (stripos($rawText, 'garder') !== false) ? 'garder' : ((stripos($rawText, 'rejeter') !== false) ? 'rejeter' : 'indéterminé');
        return [
            'firstName' => '', 'lastName' => '', 'email' => '', 'phone' => '', 'educationLevel' => '', 'skills' => '', 'jobTitle' => '',
            'recommendation' => $rec,
            'reason' => 'Analyse partielle. Réponse brute : ' . $this->truncate($rawText, 200)
        ];
    }

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
        if (strlen($text) <= $max) return $text;
        return substr($text, 0, $max) . '...';
    }
    // src/Service/HuggingFaceAnalyzer.php
// Ajoute cette méthode à la fin de la classe (avant la dernière accolade)

    public function generateRejectionLetter(string $firstName, string $lastName, string $jobTitle, string $reason): string
    {
        $systemMessage = [
            'role' => 'system',
            'content' => "Tu es un responsable RH professionnel chez INTEGRA Recruitment. Tu rédiges des lettres de refus courtes, respectueuses, empathiques et professionnelles en français. Réponds UNIQUEMENT avec le texte de la lettre, sans titre, sans objet, sans mise en forme markdown."
        ];

        $userContent = sprintf(
            "Rédige une lettre de refus de candidature pour :\n- Candidat : %s %s\n- Poste : %s\n- Raison du refus (interne, ne pas la mentionner telle quelle) : %s\n\nLa lettre doit :\n1. Commencer par 'Bonjour %s %s,'\n2. Remercier chaleureusement le candidat pour sa candidature\n3. Annoncer poliment que sa candidature n'a pas été retenue\n4. Encourager le candidat pour la suite\n5. Se terminer par une formule de politesse et 'L'équipe INTEGRA Recruitment'\n6. Être concise (3-4 paragraphes maximum)\n7. Être bienveillante et professionnelle",
            $firstName, $lastName, $jobTitle, $reason,
            $firstName, $lastName
        );

        $userMessage = [
            'role' => 'user',
            'content' => $userContent
        ];

        $messages = [$systemMessage, $userMessage];

        $body = [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => 500,
            'temperature' => 0.7,
            'stream' => false,
        ];

        try {
            $response = $this->httpClient->request('POST', $this->url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
                'timeout' => 60,
            ]);

            $responseData = $response->toArray(false);
            $content = $responseData['choices'][0]['message']['content'] ?? '';
            // Nettoyer les éventuels markdown
            $content = preg_replace('/```.*?\n(.*)```/s', '$1', $content);
            return trim($content);
        } catch (\Exception $e) {
            $this->logger->error('Rejection letter generation failed', ['error' => $e->getMessage()]);
            // Fallback : lettre générique
            return sprintf(
                "Bonjour %s %s,\n\nNous vous remercions pour votre candidature au poste de %s.\n\nAprès étude attentive, nous ne pouvons pas donner suite à votre candidature.\n\nNous vous souhaitons bonne chance dans vos recherches.\n\nCordialement,\nL'équipe INTEGRA Recruitment",
                $firstName, $lastName, $jobTitle
            );
        }
    }
}