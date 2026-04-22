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

    public function generateAdvice(string $prompt): string
    {
        $url = 'https://router.huggingface.co/v1/chat/completions';

        // Model MUST include :provider suffix — without it the router returns 400
        // Options: meta-llama/Llama-3.1-8B-Instruct:cerebras  (fast, free tier)
        //          Qwen/Qwen2.5-72B-Instruct:novita
        //          mistralai/Mistral-7B-Instruct-v0.3:hf-inference
        $model = 'meta-llama/Llama-3.1-8B-Instruct:cerebras';

        $messages = [
            [
                'role'    => 'system',
                'content' => 'You are an expert HR assistant. Given a user profile, give a short actionable recommendation in 1-2 sentences. Be direct and professional.',
            ],
            [
                'role'    => 'user',
                'content' => $prompt,
            ],
        ];

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => $model,
                    'messages'    => $messages,
                    'max_tokens'  => 150,
                    'temperature' => 0.7,
                ],
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false); // false = don't throw on 4xx

            if ($statusCode !== 200) {
                $errorMsg = $data['error']['message'] ?? json_encode($data);
                throw new \Exception("HTTP $statusCode – $errorMsg");
            }

            if (isset($data['choices'][0]['message']['content'])) {
                return trim($data['choices'][0]['message']['content']);
            }

            throw new \Exception("Unexpected response format: " . json_encode($data));

        } catch (\Exception $e) {
            throw new \Exception("Erreur de l'API Hugging Face : " . $e->getMessage());
        }
    }
}