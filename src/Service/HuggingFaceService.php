<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class HuggingFaceService
{
    private const API_URL = 'https://router.huggingface.co/hf-inference/models/sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2/pipeline/feature-extraction';
    private string $apiToken;

    public function __construct(
        private HttpClientInterface $httpClient,
        string $hfToken
    ) {
        $this->apiToken = $hfToken;
    }

    public function getEmbedding(string $text): array
    {
        $response = $this->httpClient->request('POST', self::API_URL, [
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
}