<?php

namespace croacworks\essentials\controllers;

use croacworks\essentials\components\KeywordSuggester;
use yii\web\Response;
use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\models\Tag;

class TagController extends AuthorizationController
{
    /**
     * Lists all Tag models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new Tag();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Tag model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Tag model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Tag();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Tag model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Tag model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    public function actionSuggestSimple()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $title = trim((string)\Yii::$app->request->post('title', ''));
        $description = trim((string)\Yii::$app->request->post('description', ''));
        $content = trim((string)\Yii::$app->request->post('content', ''));

        $text = trim($title . ' ' . $description . ' ' . $content);
        if ($text === '') {
            return ['suggestions' => []];
        }

        $raw = KeywordSuggester::suggest($text, 12, 3);

        $normalized = [];
        foreach ($raw as $term) {
            $t = mb_strtolower($term, 'UTF-8');
            $stem = preg_replace('/(s|es|os|as|mente|ções|ção|mento|mentos|dade|dades)$/u', '', $t);
            $skip = false;
            foreach ($normalized as $n) {
                if (str_starts_with($stem, mb_strtolower($n, 'UTF-8'))) {
                    $skip = true;
                    break;
                }
            }
            if (!$skip) $normalized[] = $term;
        }
        $raw = array_slice($normalized, 0, 12);

        // Checa se já existem Tags com o mesmo nome (case-insensitive)
        $existing = [];
        if (!empty($raw)) {
            $existing = Tag::find()
                ->select(['id', 'name'])
                ->where(['lower(name)' => array_map(fn($w) => mb_strtolower($w, 'UTF-8'), $raw)])
                ->indexBy(fn($row) => mb_strtolower($row['name'], 'UTF-8'))
                ->asArray()->all();
        }

        $suggestions = [];
        foreach ($raw as $w) {
            $key = mb_strtolower($w, 'UTF-8');
            $suggestions[] = [
                'id' => $existing[$key]['id'] ?? null,
                'text' => $w,
                'exists' => isset($existing[$key]),
            ];
        }

        return ['suggestions' => $suggestions];
    }

    public function actionSuggest()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $title       = trim((string)\Yii::$app->request->post('title', ''));
        $description = trim((string)\Yii::$app->request->post('description', ''));
        $content     = trim((string)\Yii::$app->request->post('content', ''));
        $languageId  = (int)\Yii::$app->request->post('language_id', 0);

        $text = trim($title . ' ' . $description . ' ' . $content);
        if ($text === '') {
            return ['suggestions' => []];
        }

        // 🔹 Seed para diversificar respostas (mantido, mas não é estritamente necessário com a abordagem 2)
        $text .= ' ' . str_repeat('.', rand(1, 3));

        // 🔹 Idioma (pt|en|es)
        $langCode = 'pt';
        if ($languageId > 0 && ($lang = \croacworks\essentials\models\Language::findOne($languageId))) {
            $langCode = strtolower($lang->code ?? 'pt');
        }

        // 🔹 StopWords conforme idioma
        switch ($langCode) {
            case 'en':
                $stop = new \croacworks\essentials\modules\textrank\Tool\StopWords\English();
                break;
            case 'es':
                $stop = new \croacworks\essentials\modules\textrank\Tool\StopWords\Spanish();
                break;
            default:
                $stop = new \croacworks\essentials\modules\textrank\Tool\StopWords\Portuguese();
                break;
        }

        // 🔹 Usa TextRank do seu fork
        // O TextRankFacade não precisa receber as StopWords diretamente no getOnlyKeyWords.
        // O método setStopWords deve ser usado antes.
        $api = new \croacworks\essentials\modules\textrank\TextRankFacade();
        $api->setStopWords($stop);
        $keywordsAssoc = $api->getOnlyKeyWords($text); // ['fedora' => score, ...]

        // 🔹 Pós-filtro: ignora termos com peso muito baixo (<20% do topo)
        if (!empty($keywordsAssoc)) {
            $max = max($keywordsAssoc);
            $keywordsAssoc = array_filter($keywordsAssoc, fn($v) => $v >= 0.2 * $max);
        }

        arsort($keywordsAssoc);

        // 🔹 Normaliza (Aplicação da Abordagem 2 para diversificar)

        // 1. Aumenta o pool para, por exemplo, as top 20 keywords.
        $rawPool = array_slice(array_keys($keywordsAssoc), 0, 30);

        // 2. Embaralha o pool para introduzir aleatoriedade.
        shuffle($rawPool);

        // 3. Seleciona as 12 keywords finais do pool embaralhado.
        $raw = array_slice($rawPool, 0, 12);

        $raw = array_values(array_unique(array_map(
            fn($w) => mb_convert_case($w, MB_CASE_TITLE, 'UTF-8'),
            $raw
        )));

        // 🔹 Remove duplicatas e plurais simples
        $normalized = [];
        foreach ($raw as $term) {
            // Estemming simplificado
            $stem = preg_replace('/(s|es|os|as|mente|ções|ção|mento|mentos|dade|dades)$/u', '', mb_strtolower($term, 'UTF-8'));
            $skip = false;
            foreach ($normalized as $n) {
                // Compara se o stem atual está contido no stem de um termo já aceito
                if (str_starts_with($stem, preg_replace('/(s|es|os|as|mente|ções|ção|mento|mentos|dade|dades)$/u', '', mb_strtolower($n, 'UTF-8')))) {
                    $skip = true;
                    break;
                }
            }
            if (!$skip) {
                $normalized[] = $term;
            }
        }
        $raw = array_slice($normalized, 0, 12);

        $existing = [];
        if (!empty($raw)) {
            $existing = Tag::find()
                ->select(['id', 'name'])
                ->where(['lower(name)' => array_map(fn($w) => mb_strtolower($w, 'UTF-8'), $raw)])
                ->indexBy(fn($row) => mb_strtolower($row['name'], 'UTF-8'))
                ->asArray()->all();
        }

        // 🔹 Monta resposta
        $suggestions = [];
        foreach ($raw as $w) {
            $key = mb_strtolower($w, 'UTF-8');
            $suggestions[] = [
                'id'     => $existing[$key]['id'] ?? null,
                'text'   => $w,
                'exists' => isset($existing[$key]),
            ];
        }

        return ['suggestions' => $suggestions];
    }

    public function actionSummarize()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $title       = trim((string)\Yii::$app->request->post('title', ''));
        $description = trim((string)\Yii::$app->request->post('description', ''));
        $content     = trim((string)\Yii::$app->request->post('content', ''));
        $languageId  = (int)\Yii::$app->request->post('language_id', 0);

        $text = trim($title . ' ' . $description . ' ' . $content);
        if ($text === '') {
            return ['success' => false, 'summary' => '', 'message' => 'Texto vazio'];
        }

        $langCode = 'pt';
        if ($languageId > 0 && ($lang = \croacworks\essentials\models\Language::findOne($languageId))) {
            $langCode = strtolower($lang->code ?? 'pt');
        }

        switch ($langCode) {
            case 'en':
                $stop = new \croacworks\essentials\modules\textrank\Tool\StopWords\English();
                break;
            case 'es':
                $stop = new \croacworks\essentials\modules\textrank\Tool\StopWords\Spanish();
                break;
            default:
                $stop = new \croacworks\essentials\modules\textrank\Tool\StopWords\Portuguese();
                break;
        }

        try {
            $api = new \croacworks\essentials\modules\textrank\TextRankFacade();
            $highlights = $api->getHighlights($text, $stop); // array de frases ordenadas por relevância
            $summary = implode(' ', array_slice($highlights, 0, 2)); // 1–2 frases

            $summary = preg_replace('/\s+/', ' ', trim($summary));
            if (mb_strlen($summary, 'UTF-8') > 160) {
                $summary = mb_substr($summary, 0, 157, 'UTF-8') . '...';
            }

            return ['success' => true, 'language' => $langCode, 'summary' => $summary];
        } catch (\Throwable $e) {
            \Yii::error("TextRank summarize error: {$e->getMessage()}", __METHOD__);
            return ['success' => false, 'language' => $langCode, 'summary' => '', 'message' => $e->getMessage()];
        }
    }

    /** GET /tag/search?q=css  →  [{id:1,text:"CSS"}, ...] */
    public function actionSearch(string $q = '')
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        if ($q === '') return [];

        $rows = Tag::find()
            ->select(['id', 'text' => 'name'])
            ->where(['status' => 1])
            ->andWhere(['like', 'name', $q])
            ->orderBy(['name' => SORT_ASC])
            ->limit(20)
            ->asArray()->all();

        return $rows;
    }
}
