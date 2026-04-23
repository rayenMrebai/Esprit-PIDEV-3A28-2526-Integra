<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class AIQuizGenerator
{
    private $apiKey;
    private $apiUrl;
    private $model;
    private $httpClient;
    private $logger;

    public function __construct(
        string $apiKey, 
        string $apiUrl, 
        string $model,
        HttpClientInterface $httpClient
    ) {
        // FORCE MANUELLE DE LA BONNE CLÉ API
        // La clé qui fonctionne en PowerShell
        $this->apiKey = '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';
        $this->apiUrl = $apiUrl;
        $this->model = $model;
        $this->httpClient = $httpClient;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function generateQuiz(string $formationTitle, string $formationDescription): array
    {
        // Log pour debug
        if ($this->logger) {
            $this->logger->info('🔑 Clé API utilisée (forcée manuellement)', [
                'prefix' => substr($this->apiKey, 0, 20) . '...',
                'length' => strlen($this->apiKey),
                'full_key_matches' => $this->apiKey === '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',
                'url' => $this->apiUrl,
                'model' => $this->model
            ]);
        }
        
        if (empty($this->apiKey) || str_starts_with($this->apiKey, 'your-')) {
            if ($this->logger) {
                $this->logger->error('AIQuizGenerator: clé API manquante ou invalide');
            }
            return $this->getFallbackQuiz($formationTitle);
        }

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => $this->model,
                    'messages'    => [
                        [
                            'role'    => 'system',
                            'content' => 'Tu es un expert en création de quiz éducatifs. Retourne UNIQUEMENT un tableau JSON valide, sans aucun texte avant ou après, sans balises markdown.',
                        ],
                        [
                            'role'    => 'user',
                            'content' => $this->buildPrompt($formationTitle, $formationDescription),
                        ],
                    ],
                    'temperature' => 0.7,
                    'max_tokens'  => 2000,
                ],
                'timeout'     => 30,
                'verify_peer' => false,
                'verify_host' => false,
            ]);

            $statusCode = $response->getStatusCode();

            if ($this->logger) {
                $this->logger->info('AIQuizGenerator: HTTP status', ['status' => $statusCode]);
            }

            if ($statusCode !== 200) {
                $errorBody = $response->getContent(false);
                if ($this->logger) {
                    $this->logger->error('AIQuizGenerator: HTTP ' . $statusCode, [
                        'body' => $errorBody,
                    ]);
                }
                return $this->getFallbackQuiz($formationTitle);
            }

            $data = $response->toArray();

            if (!isset($data['choices'][0]['message']['content'])) {
                if ($this->logger) {
                    $this->logger->error('AIQuizGenerator: structure inattendue', $data);
                }
                return $this->getFallbackQuiz($formationTitle);
            }

            $content = $data['choices'][0]['message']['content'];
            if ($this->logger) {
                $this->logger->info('AIQuizGenerator: réponse reçue', ['content' => $content]);
            }

            return $this->parseAIResponse($content);

        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('AIQuizGenerator EXCEPTION: ' . $e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
            return $this->getFallbackQuiz($formationTitle);
        }
    }

    private function buildPrompt(string $title, string $description): string
    {
        return sprintf(
            'Génère exactement 10 questions à choix multiple spécifiques à : "%s".
Description de la formation : %s

Retourne UNIQUEMENT ce tableau JSON, sans texte avant ou après, sans balises markdown :

[
  {
    "question": "Question précise sur %s ?",
    "options": ["Option A", "Option B", "Option C", "Option D"],
    "correct": 0
  }
]

Règles strictes :
- Exactement 10 questions
- Exactement 4 options par question
- "correct" est l\'index 0-3 de la bonne réponse
- Les questions doivent porter sur le contenu réel de "%s", pas sur la formation en général
- Difficulté progressive, niveau intermédiaire',
            $title,
            $description ?: 'Formation professionnelle',
            $title,
            $title
        );
    }

    private function parseAIResponse(string $response): array
    {
        $response = trim($response);

        // Supprimer les balises markdown
        $response = preg_replace('/^```json\s*/m', '', $response);
        $response = preg_replace('/^```\s*$/m', '', $response);
        $response = trim($response);

        // Extraire le tableau JSON
        $firstBracket = strpos($response, '[');
        $lastBracket  = strrpos($response, ']');

        if ($firstBracket === false || $lastBracket === false || $lastBracket <= $firstBracket) {
            throw new \Exception('Aucun tableau JSON trouvé : ' . substr($response, 0, 200));
        }

        $jsonString = substr($response, $firstBracket, $lastBracket - $firstBracket + 1);
        $questions  = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON invalide : ' . json_last_error_msg());
        }

        if (!is_array($questions) || count($questions) < 5) {
            throw new \Exception('Seulement ' . count($questions ?? []) . ' questions reçues');
        }

        // Valider et corriger chaque question
        $validated = [];
        foreach ($questions as $q) {
            if (!isset($q['question'], $q['options'], $q['correct'])) {
                continue;
            }
            if (!is_array($q['options']) || count($q['options']) !== 4) {
                continue;
            }
            $q['correct'] = max(0, min(3, (int) $q['correct']));
            $validated[]  = $q;
        }

        if (count($validated) < 5) {
            throw new \Exception('Pas assez de questions valides : ' . count($validated));
        }

        return $validated;
    }

    private function getFallbackQuiz(string $title): array
    {
        return [
            [
                'question' => "Qu'est-ce que {$title} ?",
                'options'  => ['Une méthode de travail', 'Une technologie spécifique', 'Un concept fondamental', 'Une certification'],
                'correct'  => 0,
            ],
            [
                'question' => "Pourquoi {$title} est-il important ?",
                'options'  => ['Pour améliorer la productivité', 'Pour obtenir une certification', 'Par obligation légale', 'Pour le plaisir'],
                'correct'  => 0,
            ],
            [
                'question' => "Quel est le prérequis pour apprendre {$title} ?",
                'options'  => ['Aucun prérequis', 'Niveau débutant', 'Niveau intermédiaire', 'Niveau avancé'],
                'correct'  => 1,
            ],
            [
                'question' => "Combien de temps faut-il pour maîtriser {$title} ?",
                'options'  => ['Quelques jours', 'Quelques semaines', 'Quelques mois', "Cela dépend de l'apprenant"],
                'correct'  => 3,
            ],
            [
                'question' => "{$title} est-il reconnu professionnellement ?",
                'options'  => ['Oui, très reconnu', 'Non, pas du tout', 'Reconnu dans certains secteurs', 'En cours de reconnaissance'],
                'correct'  => 0,
            ],
            [
                'question' => "Quel est le coût moyen pour une formation {$title} ?",
                'options'  => ['Gratuit', 'Moins de 500€', 'Entre 500€ et 2000€', 'Plus de 2000€'],
                'correct'  => 1,
            ],
            [
                'question' => "Quel format est le plus adapté pour {$title} ?",
                'options'  => ['100% en ligne', '100% présentiel', 'Mixte (hybride)', 'Auto-formation'],
                'correct'  => 2,
            ],
            [
                'question' => "{$title} est-il éligible au CPF ?",
                'options'  => ['Oui', 'Non', "Selon l'organisme", "En cours d'homologation"],
                'correct'  => 2,
            ],
            [
                'question' => "Quel est le niveau de difficulté de {$title} ?",
                'options'  => ['Débutant', 'Intermédiaire', 'Avancé', 'Expert'],
                'correct'  => 1,
            ],
            [
                'question' => "Quel est l'avenir de {$title} ?",
                'options'  => ['En pleine expansion', 'En déclin', 'Stable', 'Incertain'],
                'correct'  => 0,
            ],
        ];
    }
}