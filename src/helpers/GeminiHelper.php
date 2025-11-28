<?php

namespace croacworks\essentials\helpers;

use Yii;
use Exception;

class GeminiHelper
{
    /**
     * Sends a request to Google Gemini API.
     *
     * @param string $instruction System instruction.
     * @param string $content User content.
     * @param float $temperature Creativity level.
     * @param int $maxOutputTokens Max response size (default 2048).
     * @return string|null
     * @throws Exception
     */
    public static function processRequest($instruction, $content, $temperature = 0.7, $maxOutputTokens = 2048)
    {
        $apiKey = Yii::$app->params['gemini']['apiKey'] ?? null;
        $baseUrl = Yii::$app->params['gemini']['url'] ?? 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

        if (!$apiKey) {
            throw new Exception(Yii::t('app', 'Gemini API Key not configured.'));
        }

        $url = $baseUrl . "?key=" . $apiKey;

        // Construct prompt
        $fullPrompt = $instruction . "\n\n---\n\nInput: " . $content;

        $payload = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $fullPrompt]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => (float)$temperature,
                "maxOutputTokens" => (int)$maxOutputTokens, // Uses the dynamic limit
            ]
        ];

        // Execute cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception(Yii::t('app', 'Connection error with Google: {0}', [$error]));
        }

        curl_close($ch);

        $json = json_decode($response, true);

        if (isset($json['error'])) {
            throw new Exception(Yii::t('app', 'Gemini API Error: {0}', [$json['error']['message']]));
        }

        if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            return $json['candidates'][0]['content']['parts'][0]['text'];
        }

        return null;
    }

    public static function cleanMarkdown($text)
    {
        if (!$text) return '';
        $text = preg_replace('/^```[a-z]*\n/', '', $text);
        $text = preg_replace('/\n```$/', '', $text);
        return trim($text);
    }
}
