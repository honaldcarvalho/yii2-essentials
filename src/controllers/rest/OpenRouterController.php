<?php

namespace croacworks\essentials\controllers\rest;

use Yii;
use yii\httpclient\Client;
use yii\web\Response;

class OpenRouterController extends ControllerRest
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Configuração de CORS (importante se for acessado por frontend JS externo)
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
        ];

        // Define o formato de resposta sempre como JSON
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;

        return $behaviors;
    }

    /**
     * Ação para enviar mensagens ao OpenRouter
     * POST /open-router/chat
     */
    public function actionChat()
    {
        $request = Yii::$app->request;

        // 1. Obter dados do POST
        $userMessage = $request->post('message');
        $model = $request->post('model', 'google/gemini-2.0-flash-exp:free'); // Modelo default

        if (!$userMessage) {
            Yii::$app->response->statusCode = 400;
            return ['error' => 'O parâmetro "message" é obrigatório.'];
        }

        // 2. Preparar o cliente HTTP
        $client = new Client();
        $apiKey = Yii::$app->params['openRouterApiKey'];

        try {
            // 3. Configurar e enviar a requisição
            $response = $client->createRequest()
                ->setMethod('POST')
                ->setUrl('https://openrouter.ai/api/v1/chat/completions')
                ->addHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    // Headers exigidos/recomendados pelo OpenRouter para rankings
                    'HTTP-Referer' => Yii::$app->params['siteUrl'] ?? 'http://localhost',
                    'X-Title' => Yii::$app->params['siteName'] ?? 'Yii2 App',
                    'Content-Type' => 'application/json',
                ])
                ->setFormat(Client::FORMAT_JSON)
                ->setData([
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $userMessage
                        ]
                    ],
                    // Outros parâmetros opcionais
                    // 'temperature' => 0.7,
                    // 'max_tokens' => 1000,
                ])
                ->send();

            // 4. Verificar sucesso da requisição externa
            if ($response->isOk) {
                return $response->data;
            } else {
                // Repassar erro do OpenRouter para o cliente
                Yii::$app->response->statusCode = $response->statusCode;
                return [
                    'error' => 'Erro na API OpenRouter',
                    'details' => $response->data
                ];
            }
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 500;
            return [
                'error' => 'Erro interno ao comunicar com IA',
                'message' => $e->getMessage()
            ];
        }
    }
}
