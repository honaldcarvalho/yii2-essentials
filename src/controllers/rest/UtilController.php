<?php

namespace croacworks\essentials\controllers\rest;

use croacworks\essentials\controllers\rest\GeminiController;
use croacworks\essentials\helpers\TranslatorHelper;
use Yii;

/**
 * FileController implements the CRUD actions for File model.
 */
class UtilController extends ControllerRest
{

    /**
     * @param string $language Target language (e.g. 'pt', 'en')
     * @param string $to Source language (default 'auto')
     * @param string $provider 'default' ou 'gemini'
     */
    public function actionSuggestTranslation($language, $to = 'auto', $provider = 'default')
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $body = Yii::$app->request->getBodyParams();
        $text = $body['text'] ?? null;

        if (!$text) {
            return ['success' => false, 'message' => Yii::t('app', 'Missing "text" parameter.')];
        }

        try {
            $translated = null;

            if ($provider === 'gemini') {
                // 1. Prepara o Prompt de tradução
                $sourceInstruction = ($to === 'auto') ? "Detect language" : "From {$to}";

                $instruction = "You are a professional technical translator. {$sourceInstruction} to {$language}. " .
                    "Return ONLY the translated text. Do not include markdown or explanations.";

                // 2. Chama o método ESTÁTICO do GeminiController
                // Usamos temperatura 0.1 para maior precisão na tradução
                $rawResult = GeminiController::processRequest($instruction, $text, 0.1);

                // 3. Limpa o resultado
                $translated = GeminiController::cleanMarkdown($rawResult);
            } else {
                // Fallback para o tradutor antigo
                $translated = TranslatorHelper::translate($text, $language, $to);
            }

            return [
                'success' => true,
                'translation' => $translated,
                'provider' => $provider
            ];
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'message' => Yii::t('app', 'Error while suggesting translation.'),
                'error' => YII_DEBUG ? $e->getMessage() : null,
            ];
        }
    }
}
