<?php

namespace croacworks\essentials\controllers\rest;

use Yii;
use croacworks\essentials\helpers\GeminiHelper;

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

        $instruction = $request->post('instruction', Yii::t('app', 'You are a helpful assistant.'));
        $content = $request->post('content');
        $temperature = $request->post('temperature', 0.7);

        if (!$content) {
            Yii::$app->response->statusCode = 400;
            return [
                'status' => 'error',
                'message' => Yii::t('app', 'The "content" field is required.')
            ];
        }

        try {
            // Uses the new Helper
            $result = GeminiHelper::processRequest($instruction, $content, $temperature);

            return [
                'status' => 'success',
                'data' => [
                    'raw_response' => $result,
                    'cleaned_response' => GeminiHelper::cleanMarkdown($result)
                ]
            ];
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 500;
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
