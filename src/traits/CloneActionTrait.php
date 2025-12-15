<?php

namespace croacworks\essentials\traits;

use croacworks\essentials\services\PageCloneService;
use croacworks\essentials\models\Language;
use Yii;
use yii\web\NotFoundHttpException;

trait CloneActionTrait
{
    /**
     * Prepares the clone draft object.
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

        if (property_exists($clone, 'tagIds') && property_exists($original, 'tagIds')) {
            $clone->tagIds = $original->tagIds;
        }

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
     * Delegates saving logic to PageCloneService.
     */
    protected function processCloneSave($originalId, $clone, $modelClass)
    {
        $original = $modelClass::findOne($originalId);
        if (!$original) {
            throw new NotFoundHttpException(Yii::t('app', 'Original not found.'));
        }

        $overrides = [];

        if (isset($clone->tagIds)) {
            $overrides['tagIds'] = $clone->tagIds;
        }

        $attributesToCompare = $original->attributes();
        foreach ($attributesToCompare as $key) {
            if ($key === 'id') continue;

            if (!is_array($clone->$key) && $clone->$key !== $original->$key) {
                $overrides[$key] = $clone->$key;
            }
        }

        // Determine clone type
        $langAttr = 'language_id';
        $originalLang = $original->$langAttr ?? null;
        $newLang = $clone->$langAttr ?? ($overrides[$langAttr] ?? null);

        $isLanguageClone = ($originalLang && $newLang && $originalLang !== $newLang);

        try {
            if ($isLanguageClone) {
                $newPage = PageCloneService::cloneLanguage($original, $overrides);
                Yii::$app->session->addFlash('success', Yii::t('app', 'Language clone created successfully.'));
            } else {
                $newPage = PageCloneService::cloneTotal($original, $overrides);
                Yii::$app->session->addFlash('success', Yii::t('app', 'Total clone created (new group).'));
            }

            return $newPage;
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('danger', Yii::t('app', 'Failed to clone: {msg}', ['msg' => $e->getMessage()]));
            return null;
        }
    }
}
