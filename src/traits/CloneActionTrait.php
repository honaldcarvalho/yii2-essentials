<?php

namespace croacworks\essentials\traits;

use croacworks\essentials\models\Language;
use croacworks\essentials\services\PageCloneService;
use Yii;
use yii\web\NotFoundHttpException;

trait CloneActionTrait
{
    /**
     * Prepares the clone draft, including attributes copy and optional translation.
     *
     * @param int $id Original model ID.
     * @param string $modelClass Full class name of the model (e.g., Page::class).
     * @param string|null $target_lang Language code to translate to.
     * @param string $provider Translation provider.
     * @return \yii\db\ActiveRecord
     * @throws NotFoundHttpException
     */
    protected function prepareCloneDraft($id, $modelClass, $target_lang = null, $provider = 'default')
    {
        /** @var \yii\db\ActiveRecord $original */
        $original = $modelClass::findOne($id);

        if (!$original) {
            throw new NotFoundHttpException(Yii::t('app', 'Item with ID {id} not found.', ['id' => $id]));
        }

        /** @var \yii\db\ActiveRecord $clone */
        $clone = new $modelClass();
        $clone->attributes = $original->attributes;
        $clone->setIsNewRecord(true);
        $clone->id = null;

        // Copy special property tagIds if exists
        if (property_exists($clone, 'tagIds') && property_exists($original, 'tagIds')) {
            $clone->tagIds = $original->tagIds;
        }

        // Apply translation if requested
        if ($target_lang) {
            $languageModel = Language::findOne(['code' => $target_lang]);

            if ($languageModel) {
                if ($clone->hasAttribute('language_id')) {
                    $clone->language_id = $languageModel->id;
                }

                if (method_exists($clone, 'translateContent')) {
                    $clone->translateContent($target_lang, $provider);

                    Yii::$app->session->addFlash('info', Yii::t('app', 'Content auto-translated to {0} using {1}. Please review before saving.', [
                        $languageModel->name,
                        ucfirst($provider)
                    ]));
                }
            }
        }

        return $clone;
    }

    /**
     * Handles the saving logic, logic comparison, and service calls.
     *
     * @param int $originalId The ID of the original record.
     * @param \yii\db\ActiveRecord $clone The populated clone model.
     * @param string $modelClass The class name to find the original.
     * @return \yii\db\ActiveRecord|null Returns the new model on success, or null on failure.
     */
    protected function processCloneSave($originalId, $clone, $modelClass)
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $original = $modelClass::findOne($originalId);

            // Build overrides
            $overrides = [];

            // Handle tagIds explicitly if property exists
            if (isset($clone->tagIds)) {
                $overrides['tagIds'] = $clone->tagIds;
            }

            // Detect manual changes
            $attributesToCompare = $original->attributes();
            foreach ($attributesToCompare as $key) {
                if ($key === 'id') continue;

                if (!is_array($clone->$key) && $clone->$key !== $original->$key) {
                    $overrides[$key] = $clone->$key;
                }
            }

            // Determine clone type (Language vs Total)
            $langAttr = 'language_id';
            $originalLang = $original->$langAttr ?? null;
            $newLang = $clone->$langAttr ?? ($overrides[$langAttr] ?? null);

            $isLanguageClone = ($originalLang && $newLang && $originalLang !== $newLang);

            if ($isLanguageClone) {
                $newPage = PageCloneService::cloneLanguage($original, $overrides);
                Yii::$app->session->addFlash('success', Yii::t('app', 'Language clone created successfully.'));
            } else {
                $newPage = PageCloneService::cloneTotal($original, $overrides);
                Yii::$app->session->addFlash('success', Yii::t('app', 'Total clone created (new group).'));
            }

            $transaction->commit();
            return $newPage;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('danger', Yii::t('app', 'Failed to clone: {msg}', ['msg' => $e->getMessage()]));
            return null;
        }
    }
}
