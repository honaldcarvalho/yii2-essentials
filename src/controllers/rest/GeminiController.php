<?php

namespace croacworks\essentials\controllers\rest;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\helpers\Json;

class GeminiController extends ControllerRest
{
    // Define o formato de resposta sempre como JSON
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_JSON;
        // Desabilita verificação CSRF para facilitar testes via Postman/cURL
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
        ];
        return $behaviors;
    }

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    /**
     * Endpoint Genérico: POST /gemini/ask
     * Body esperado (JSON):
     * {
     * "instruction": "Você é um tradutor...", (Opcional - System Prompt)
     * "content": "Texto a ser processado...", (Obrigatório)
     * "temperature": 0.5 (Opcional, 0.0 a 1.0)
     * }
     */
    public function actionAsk()
    {
        $request = Yii::$app->request;

        $instruction = $request->post('instruction', 'Você é um assistente útil.');
        $content = $request->post('content');
        $temperature = $request->post('temperature', 0.7); // Padrão equilibrado

        if (!$content) {
            Yii::$app->response->statusCode = 400;
            return ['status' => 'error', 'message' => 'O campo "content" é obrigatório.'];
        }

        try {
            $result = $this->callGeminiApi($instruction, $content, $temperature);

            return [
                'status' => 'success',
                'data' => [
                    'raw_response' => $result,
                    'cleaned_response' => $this->cleanMarkdown($result) // Remove ```json ... ``` se houver
                ]
            ];
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 500;
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Faz a chamada real à API
     */
    private function callGeminiApi($instruction, $content, $temperature)
    {
        // RECOMENDAÇÃO: Coloque sua chave no params-local.php ou .env
        $apiKey = Yii::$app->params['gemini']['apiKey'];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

        // Combina instrução e conteúdo
        // Existem formas de passar System Instruction separado, mas concatenar funciona bem no Flash e é mais simples
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

        // Em produção, mantenha SSL verify como true. Em local windows as vezes precisa false.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \Exception('Erro de conexão com o Google: ' . curl_error($ch));
        }

        curl_close($ch);

        $json = json_decode($response, true);

        if (isset($json['error'])) {
            throw new \Exception('Erro da API Gemini: ' . $json['error']['message']);
        }

        if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            return $json['candidates'][0]['content']['parts'][0]['text'];
        }

        return null;
    }

    /**
     * Remove formatação Markdown (```json ... ```) caso a IA retorne código
     */
    private function cleanMarkdown($text)
    {
        $text = preg_replace('/^```[a-z]*\n/', '', $text);
        $text = preg_replace('/\n```$/', '', $text);
        return trim($text);
    }
}
