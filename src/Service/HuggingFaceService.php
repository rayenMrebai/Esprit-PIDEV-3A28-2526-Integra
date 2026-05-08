<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class HuggingFaceService
{
    private HttpClientInterface $httpClient;
    private string $apiToken;

    public function __construct(HttpClientInterface $httpClient, string $apiToken)
    {
        $this->httpClient = $httpClient;
        $this->apiToken = $apiToken;
    }

    /**
     * @throws \Exception
     */
    public function generateAdvice(string $prompt): string
    {
        $url = 'https://router.huggingface.co/v1/chat/completions';
        $model = 'meta-llama/Llama-3.1-8B-Instruct:cerebras';

        $messages = [
            ['role' => 'system', 'content' => 'You are an expert HR assistant. Given a user profile, give a short actionable recommendation in 1-2 sentences. Be direct and professional.'],
            ['role' => 'user', 'content' => $prompt],
        ];

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => $messages,
                    'max_tokens' => 150,
                    'temperature' => 0.7,
                ],
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($statusCode !== 200) {
                $errorMsg = $this->extractErrorMessage($data);
                throw new \Exception("HTTP $statusCode – $errorMsg");
            }

            if (is_array($data) && isset($data['choices'][0]['message']['content'])) {
                return trim((string)$data['choices'][0]['message']['content']);
            }

            throw new \Exception("Unexpected response format: " . json_encode($data));
        } catch (\Exception $e) {
            throw new \Exception("Erreur de l'API Hugging Face : " . $e->getMessage());
        }
    }

    /**
     * @param array<mixed>|string $data
     */
    private function extractErrorMessage($data): string
    {
        if (is_array($data) && isset($data['error']['message']) && is_string($data['error']['message'])) {
            return $data['error']['message'];
        }
        return json_encode($data) ?: 'Unknown error';
    }
}