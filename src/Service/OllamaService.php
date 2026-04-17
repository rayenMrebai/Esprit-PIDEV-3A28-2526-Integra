<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OllamaService
{
    private string $baseUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
        string $ollamaBaseUrl = 'http://localhost:11434'
    ) {
        $this->baseUrl = rtrim($ollamaBaseUrl, '/');
    }

    /**
     * Génère une réponse à partir d'un prompt.
     * @param string $prompt
     * @param string $model Nom du modèle Ollama (ex: mistral, gemma2, llama3)
     * @return string
     */
    public function generate(string $prompt, string $model = 'mistral'): string
    {
        $response = $this->httpClient->request('POST', $this->baseUrl . '/api/generate', [
            'json' => [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => 0.7,
                    'num_predict' => 512,
                ],
            ],
            'timeout' => 120,
        ]);

        $data = $response->toArray(false);
        return trim($data['response'] ?? '');
    }
}