<?php

declare(strict_types=1);

namespace GameAdmin\Service;

class GeminiService
{
    private string $apiKey;
    private string $model;

    public function __construct(?string $apiKey = null, string $model = 'gemini-1.5-flash')
    {
        $this->apiKey = $apiKey ?? (string) (getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? ''));
        $this->model  = $model;
    }

    /**
     * Summarize game description using Gemini API
     */
    public function summarizeDescription(string $description): string
    {
        $description = trim($description);
        if (empty($description)) {
            return '';
        }

        $prompt = "You are an expert AI assistant specializing in educational gaming and pediatric cognitive development. "
            . "Summarize the following game description into 2 to 3 clear, highly engaging, professional sentences highlighting the core learning objectives and cognitive skills targeted.\n\n"
            . "Description:\n" . $description;

        $response = $this->callGeminiApi($prompt);

        if (!empty($response)) {
            return trim($response);
        }

        // Fallback summary generator if API key missing or offline
        $sentences = preg_split('/(?<=[.?!])\s+/', $description);
        if (count($sentences) > 0 && !empty($sentences[0])) {
            return implode(' ', array_slice($sentences, 0, 2));
        }

        return substr($description, 0, 200) . '...';
    }

    /**
     * Extract searchable tags from game description based on Early Childhood Education,
     * Neurodevelopmental Disorders, and Child Education relation.
     */
    public function extractSearchableTags(string $description): array
    {
        $description = trim($description);
        if (empty($description)) {
            return [];
        }

        $prompt = "You are a specialized taxonomy assistant for pediatric cognitive education. "
            . "Analyze the following game description and extract relevant keywords and tags based specifically on:\n"
            . "1. Early Childhood Education\n"
            . "2. Neurodevelopmental Disorders (e.g. Dyslexia, ADHD, Dyscalculia, Autism, Executive Function, Auditory Processing, Working Memory, Special Needs Education)\n"
            . "3. Child Education Relation and Pedagogy.\n\n"
            . "Return ONLY a valid JSON array of lowercase string tags (for example: [\"early-childhood-education\", \"dyslexia\", \"phonics\", \"executive-function\", \"adhd\", \"working-memory\"]). Do NOT include markdown code blocks, backticks, or any conversational text.\n\n"
            . "Description:\n" . $description;

        $responseText = $this->callGeminiApi($prompt);

        if (!empty($responseText)) {
            // Clean up any markdown code fencing if returned by model
            $cleanJson = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($responseText));
            $tags = json_decode($cleanJson, true);
            if (is_array($tags)) {
                return array_map('strtolower', array_map('trim', $tags));
            }
        }

        // Rule-based fallback keyword extractor for Early childhood, Neurodevelopmental disorders & Child education
        return $this->fallbackTagExtractor($description);
    }

    /**
     * Makes HTTP POST request to Google Gemini API
     */
    private function callGeminiApi(string $prompt): ?string
    {
        if (empty($this->apiKey)) {
            return null;
        }

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            urlencode($this->model),
            urlencode($this->apiKey)
        );

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'maxOutputTokens' => 500,
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && is_string($response)) {
            $json = json_decode($response, true);
            if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
                return $json['candidates'][0]['content']['parts'][0]['text'];
            }
        }

        return null;
    }

    /**
     * Fallback tag extraction when offline or API key unconfigured
     */
    private function fallbackTagExtractor(string $text): array
    {
        $textLower = strtolower($text);
        $tags = [];

        $dictionary = [
            'early-childhood-education' => ['early', 'childhood', 'preschool', 'kindergarten', 'toddler', 'early education', 'foundational'],
            'dyslexia'                 => ['dyslexia', 'reading', 'phonics', 'phoneme', 'decoding', 'literacy', 'letter'],
            'adhd'                     => ['adhd', 'attention', 'focus', 'hyperactivity', 'impulse', 'executive function'],
            'dyscalculia'              => ['dyscalculia', 'math', 'number', 'spatial', 'counting', 'arithmetic'],
            'neurodevelopmental'       => ['neurodevelopmental', 'cognitive', 'brain', 'intervention', 'therapy', 'special needs'],
            'working-memory'           => ['memory', 'working memory', 'recall', 'pattern recognition', 'retention'],
            'child-education'          => ['child education', 'pedagogy', 'learning', 'classroom', 'student', 'interactive'],
        ];

        foreach ($dictionary as $tag => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($textLower, $kw)) {
                    $tags[] = $tag;
                    break;
                }
            }
        }

        if (empty($tags)) {
            $tags = ['early-childhood-education', 'child-education', 'cognitive-development'];
        }

        return array_unique($tags);
    }
}
