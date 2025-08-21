<?php

namespace croacworks\essentials\helpers;

use Stichoza\GoogleTranslate\GoogleTranslate;

class TranslatorHelper
{
    public static function translate($text, $targetLanguage, $sourceLanguage = 'en')
    {
        try {
            $tr = new GoogleTranslate($targetLanguage, $sourceLanguage);
            return $tr->translate($text);
        } catch (\Throwable $e) {
            \Yii::error("Erro na tradução automática: " . $e->getMessage());
            return null;
        }
    }
}
