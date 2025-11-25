<?php

namespace croacworks\essentials\controllers\rest;

use Yii;

class GeminiController extends ControllerRest
{

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    /**
     * POST /gemini/ask
     */
    public function actionAsk()
    {
        $request = Yii::$app->request;

        $instruction = $request->post('instruction', 'You are a helpful assistant.');
        $content = $request->post('content');
        $temperature = $request->post('temperature', 0.7);

        if (!$content) {
            Yii::$app->response->statusCode = 400;
            return ['status' => 'error', 'message' => 'The "content" field is required.'];
        }

        try {

            $result = self::processRequest($instruction, $content, $temperature);

            return [
                'status' => 'success',
                'data' => [
                    'raw_response' => $result,
                    'cleaned_response' => self::cleanMarkdown($result)
                ]
            ];
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 500;
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }


    public static function processRequest($instruction, $content, $temperature)
    {

        $apiKey = Yii::$app->params['gemini']['apiKey'] ?? null;
        $baseUrl = Yii::$app->params['gemini']['url'] ?? 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

        if (!$apiKey) {
            throw new \Exception("Gemini API Key not configured in Yii::\$app->params['gemini']['apiKey']");
        }

        $url = $baseUrl . "?key=" . $apiKey;

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
                "maxOutputTokens" => 2048,
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Change to false only if SSL error occurs locally

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \Exception('Connection error with Google: ' . curl_error($ch));
        }

        curl_close($ch);

        $json = json_decode($response, true);

        if (isset($json['error'])) {
            throw new \Exception('Gemini API Error: ' . $json['error']['message']);
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
