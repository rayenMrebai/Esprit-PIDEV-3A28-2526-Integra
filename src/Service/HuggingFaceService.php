<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class HuggingFaceService
{
    private string $apiToken;

    private const EMBEDDING_API_URL = 'https://router.huggingface.co/hf-inference/models/sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2/pipeline/feature-extraction';

    public function __construct(
        private HttpClientInterface $httpClient,
        string $HF_TOKEN
    ) {
        $this->apiToken = $HF_TOKEN;
    }

    // ============================================================
    // MATCHING (embeddings + cosine similarity)
    // ============================================================

    public function getEmbedding(string $text): array
    {
        $response = $this->httpClient->request('POST', self::EMBEDDING_API_URL, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'X-Wait-For-Model' => 'true',
            ],
            'json' => ['inputs' => $text],
            'timeout' => 60,
        ]);

        $data = $response->toArray();

        if (isset($data[0]) && is_array($data[0])) {
            $vector = $data[0];
        } elseif (isset($data[0]) && is_numeric($data[0])) {
            $vector = $data;
        } elseif (isset($data['embeddings'][0])) {
            $vector = $data['embeddings'][0];
        } else {
            throw new \RuntimeException('Format HuggingFace inattendu');
        }

        return array_map('floatval', $vector);
    }

    public function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            throw new \InvalidArgumentException('Vectors must have same length');
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        $count = count($a);

        for ($i = 0; $i < $count; $i++) {
            $dot   += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        if ($normA == 0 || $normB == 0) {
            return 0.0;
        }

        $raw = $dot / (sqrt($normA) * sqrt($normB));
        return ($raw + 1.0) / 2.0;
    }

    // ============================================================
    // CONSEILS RH (équipe)
    // ============================================================

    public function generateAdvice(string $prompt): string
    {
        $url = 'https://router.huggingface.co/v1/chat/completions';
        $model = 'meta-llama/Llama-3.1-8B-Instruct:cerebras';

        $messages = [
            [
                'role'    => 'system',
                'content' => 'You are an expert HR assistant. Give short actionable recommendation in 1-2 sentences.',
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
            $data = $response->toArray(false);

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