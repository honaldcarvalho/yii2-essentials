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
     * Hybrid Endpoint: Accepts GET params or POST JSON body.
     *
     * JSON Payload Example:
     * {
     * "language": "en",
     * "provider": "gemini",
     * "text": "Text..."
     * }
     */
    public function actionSuggestTranslation()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = Yii::$app->request;

        // Merge params (Body/JSON overrides GET)
        $params = array_merge($request->get(), $request->getBodyParams());

        $language = $params['language'] ?? null;
        $to       = $params['to'] ?? 'auto';
        $text     = $params['text'] ?? null;

        // Check 'provider' and handle typo 'provide'
        $provider = $params['provider'] ?? $params['provide'] ?? 'default';

        if (!$text) {
            return [
                'success' => false,
                'message' => Yii::t('app', 'Missing "text" parameter.')
            ];
        }

        if (!$language) {
            return [
                'success' => false,
                'message' => Yii::t('app', 'Missing "language" parameter (target language).')
            ];
        }

        try {
            $translated = null;

            if ($provider === 'gemini') {
                $sourceInstruction = ($to === 'auto') ? "Detect language" : "From {$to}";

                $instruction = "You are a professional technical translator. {$sourceInstruction} to {$language}. " .
                    "Return ONLY the translated text. Do not include markdown or explanations.";

                // Call static controller
                $rawResult = \croacworks\essentials\controllers\rest\GeminiController::processRequest($instruction, $text, 0.1);
                $translated = \croacworks\essentials\controllers\rest\GeminiController::cleanMarkdown($rawResult);
            } else {
                // Fallback to legacy translator
                $translated = TranslatorHelper::translate($text, $language, $to);
            }

            return [
                'success' => true,
                'translation' => $translated,
                'provider' => $provider,
                'target_lang' => $language
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
