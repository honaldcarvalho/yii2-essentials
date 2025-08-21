<?php

namespace croacworks\essentials\helpers;

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;

class MetaHelper
{
    public static array $validFields = [
        'title',
        'titulo',
        'description',
        'descricao',
        'resume',
        'resumo',
        'name',
        'nome',
        'keywords',
    ];

    public static function setMetaFromModel($model, array $extraKeywords = [], ?string $imageUrl = null, ?array $options = [])
    {
        if (!$model) {
            return;
        }

        $view = Yii::$app->view;

        $data = [];
        foreach (self::$validFields as $field) {
            if (isset($model->{$field}) && !empty($model->{$field})) {
                $data[$field] = strip_tags($model->{$field});
            }
        }

        $title = $options['title'] ?? $data['title'] ?? $data['name'] ?? Yii::$app->name;
        $description = $options['description'] ?? $data['description'] ?? $data['resume'] ?? reset($data) ?? Yii::$app->name;

        if($options['posfix'] ?? false) {
            $title = $title . ' - ' . $options['posfix'];
            $description = $description . ' - ' . $options['posfix'];
        }

        if($options['prefix'] ?? false) {
            $title = $options['prefix'] . ' - ' . $title;
            $description = $options['prefix'] . ' - ' . $description;
        }


        // Junta todos os campos para gerar palavras-chave
        $baseText = implode(' ', $data);

        // Filtros para keywords
        $stopWords = ['com', 'sem', 'uma', 'para', 'como', 'dos', 'das', 'nos', 'nas', 'que', 'por', 'mais', 'mas', 'não', 'sim', 'aos', 'aí', 'de', 'em', 'no', 'na', 'ao', 'as', 'os', 'e', 'o', 'a', 'é', 'se', 'do', 'da', 'ou', 'um', 'uns', 'umas', 'até', 'isso', 'esses', 'essas', 'esse', 'essa', 'lhe', 'eles', 'elas', 'ele', 'ela'];

        $words = array_filter(
            preg_split('/\s+/', mb_strtolower($baseText)),
            fn($word) => mb_strlen($word) >= 3 && !in_array($word, $stopWords)
        );

        $keywords = implode(', ', array_unique(array_merge($words, $extraKeywords)));

        // REGISTRO

        $view->registerMetaTag(['name' => 'description', 'content' => Html::encode(mb_substr($description, 0, 160))]);
        if($description !== ' - ' . $options['posfix']) $view->registerMetaTag(['name' => 'keywords', 'content' => Html::encode($keywords)]);

        // OG
        $view->registerMetaTag(['property' => 'og:title', 'content' => Html::encode($title)]);
        if($description !== ' - ' . $options['posfix']) $view->registerMetaTag(['property' => 'og:description', 'content' => Html::encode($description)]);
        $view->registerMetaTag(['property' => 'og:type', 'content' => 'article']);
        $view->registerMetaTag(['property' => 'og:url', 'content' => Yii::$app->request->absoluteUrl]);

        if($imageUrl) $view->registerMetaTag(['property' => 'og:image', 'content' => $imageUrl]);

        // Twitter
        $view->registerMetaTag(['name' => 'twitter:card', 'content' => 'summary_large_image']);
        $view->registerMetaTag(['name' => 'twitter:title', 'content' => Html::encode($title)]);
        if($description !== ' - ' . $options['posfix']) $view->registerMetaTag(['name' => 'twitter:description', 'content' => Html::encode($description)]);
        if($imageUrl) $view->registerMetaTag(['name' => 'twitter:image', 'content' => $imageUrl]);
    }

    public static function setMetaForIndex(array $options = []): void
    {
        $model = $options['model'] ?? null;
        $model_options = $options['options'] ?? null;

        if (!$model) {
            $model = (object)[
                'title' => $options['title'] ?? 'Conteúdo',
                'description' => $options['description'] ?? 'Confira os conteúdos disponíveis em nosso portal.',
            ];
        }

        self::setMetaFromModel(
            $model,
            $options['keywords'] ?? [],
            $options['imageUrl'] ?? null,
            $model_options
        );
    }
}
