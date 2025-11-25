<?php

namespace croacworks\essentials\controllers\rest;

use Yii;
use yii\httpclient\Client;
use yii\rest\Controller;

class OpenRouterController extends ControllerRest
{
    /**
     * POST /open-router/chat
     */
    public function actionChat()
    {
        $request = Yii::$app->request;


        $userMessage = $request->post('message');
        $model = $request->post('model') ?? Yii::$app->params['openRouter']['model'];

        if (!$userMessage) {
            Yii::$app->response->statusCode = 400;
            return ['error' => 'The "message" parameter is required.'];
        }


        $client = new Client();
        $apiKey = Yii::$app->params['openRouter']['apiKey'];

        try {

            $response = $client->createRequest()
                ->setMethod('POST')
                ->setUrl('https://openrouter.ai/api/v1/chat/completions')
                ->addHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    // Headers required/recommended by OpenRouter for rankings
                    'HTTP-Referer' => Yii::$app->params['openRouter']['siteUrl'] ?? 'http://localhost',
                    'X-Title' => Yii::$app->params['openRouter']['siteName'] ?? 'Yii2 App',
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
                    // Other optional parameters
                    // 'temperature' => 0.7,
                    // 'max_tokens' => 1000,
                ])
                ->send();


            if ($response->isOk) {
                return $response->data;
            } else {

                Yii::$app->response->statusCode = $response->statusCode;
                return [
                    'error' => 'OpenRouter API Error',
                    'details' => $response->data
                ];
            }
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 500;
            return [
                'error' => 'Internal error communicating with AI',
                'message' => $e->getMessage()
            ];
        }
    }
}
