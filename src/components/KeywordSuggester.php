<?php
namespace  croacworks\essentials\components;

use croacworks\essentials\modules\textrank\TextRankFacade;
use croacworks\essentials\modules\textrank\Tool\StopWords\English;
use croacworks\essentials\modules\textrank\Tool\StopWords\Portuguese;
use croacworks\essentials\modules\textrank\Tool\StopWords\Spanish;
use croacworks\essentials\modules\textrank\Tool\StopWords\StopWordsAbstract;

/**
 * Keyword extractor wrapper for TextRank with language-aware stopwords.
 */
class KeywordSuggester
{
    /**
     * Retorna palavras-chave usando TextRank conforme o idioma.
     *
     * @param string      $text Texto de entrada
     * @param string|null $lang Código ISO (pt|en|es)
     * @param int         $limit Quantidade máxima de palavras-chave
     * @return string[]
     */
    public static function suggest(string $text, ?string $lang = 'pt', int $limit = 12): array
    {
        $text = trim(strip_tags($text));
        if ($text === '') {
            return [];
        }

        // Seleciona lista de stopwords
        $stop = self::getStopWordsFor($lang);

        try {
            $api = new TextRankFacade();
            $keywords = $api->getOnlyKeyWords($text, $stop);
            arsort($keywords);

            $words = array_keys(array_slice($keywords, 0, $limit, true));
            return array_map('ucfirst', array_unique($words));
        } catch (\Throwable $e) {
            \Yii::error("TextRank keyword error: {$e->getMessage()}", __METHOD__);
            return [];
        }
    }

    /**
     * Retorna a implementação correta de StopWordsInterface.
     */
    private static function getStopWordsFor(?string $lang): StopWordsAbstract
    {
        switch (strtolower($lang ?? '')) {
            case 'en':
                return new English();
            case 'es':
                return new Spanish();
            default:
                return new Portuguese();
        }
    }
}