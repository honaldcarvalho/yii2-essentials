<?php

namespace croacworks\essentials\controllers;

use croacworks\essentials\controllers\rest\StorageController;
use croacworks\essentials\models\Configuration;
use croacworks\essentials\models\Language;
use croacworks\essentials\models\Page;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * PageController implements the CRUD actions for Page model.
 */
class PageController extends AuthorizationController
{

    public $free = ['login', 'signup', 'error', 'public'];

    /**
     * Lists all Page models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new Page();
        $searchModel->scenario = Page::SCENARIO_SEARCH;
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Page model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->render('view', [
            'model' => $model
        ]);
    }

    protected function findByKey(string $slug, int $groupId, int $languageId, int $sectionId): Page
    {
        $model = Page::find()
            ->andWhere([
                'slug'        => $slug,
                'group_id'    => $groupId,
                'language_id' => $languageId,
                'page_section_id'  => $sectionId,
                'status'      => 1,
            ])
            ->one();

        if (!$model) {
            throw new NotFoundHttpException(Yii::t('app', 'Page not found or inactive.'));
        }

        return $model;
    }

    public function actionShow(
        string $page,
        int $language = 2,
        ?int $section = 1,
        ?int $group = null,
        $modal = null
    ) {
        if ($modal && (int)$modal === 1) {
            $this->layout = 'main-blank';
        }

        // group: se não vier, usa do usuário; se guest, usa 1
        $groupId = $group ?? (self::isGuest() ? 1 : (int) self::userGroup());

        $sectionId = (int)($section ?: 1);
        $languageId = (int)$language;

        $model = $this->findByKey($page, $groupId, $languageId, $sectionId);

        return $this->render('page', ['model' => $model]);
    }

public function actionPublic(
    string $slug,      // page.slug
    int $group,        // <group> da URL
    $section = null,   // section.slug (ou ID) opcional
    $lang = null,      // id OU code (ex.: 'en-US') opcional
    $modal = null
) {
    if ($modal && (int)$modal === 1) {
        $this->layout = 'main-blank';
    }

    // 1) LANGUAGE (id ou code). Se não vier, pega de Configuration::get()
    if ($lang === null || $lang === '') {
        $conf   = Configuration::get();
        $langId = (int)$conf->language_id;
    } else {
        if (is_numeric($lang)) {
            $langModel = Language::findOne((int)$lang);
        } else {
            $langModel = Language::find()->where(['code' => (string)$lang])->one();
        }
        if (!$langModel) {
            throw new NotFoundHttpException(Yii::t('app', 'Language not found.'));
        }
        $langId = (int)$langModel->id;
    }

    // 2) SECTION por (slug, group) OU por ID; se não vier, fica NULL
    $sectionId = null;
    if ($section !== null && $section !== '') {
        if (ctype_digit((string)$section)) {
            $sectionId = (int)$section;
        } else {
            $sectionId = (new \yii\db\Query())
                ->select('id')
                ->from('{{%page_sections}}')
                ->where([
                    'slug'     => (string)$section,
                    'group_id' => (int)$group,
                ])
                ->scalar() ?: null;

            if ($sectionId === null) {
                throw new NotFoundHttpException(Yii::t('app', 'Section not found.'));
            }
        }
    }

    // 3) PAGE – sem prefixo "pages." nas colunas!
    $q = Page::find()->andWhere([
        'slug'        => $slug,
        'group_id'    => (int)$group,
        'language_id' => $langId,
    ]);

    // aplica status=1 apenas se a coluna existir
    $pagesSchema = Page::getTableSchema();
    if ($pagesSchema && isset($pagesSchema->columns['status'])) {
        $q->andWhere(['status' => 1]);
    }

    if ($pagesSchema && isset($pagesSchema->columns['page_section_id'])) {
        $sectionId === null
            ? $q->andWhere(['page_section_id' => null])
            : $q->andWhere(['page_section_id' => (int)$sectionId]);
    }

    $model = $q->one();
    if (!$model) {
        throw new NotFoundHttpException(Yii::t('app', 'Page not found or inactive.'));
    }

    return $this->render('page', ['model' => $model]);
}


    /**
     * Creates a new Page model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Page();

        if ($this->request->isPost) {
            if ($model->load($this->request->post())) {
                $model->group_id   = (int) self::userGroup();
                $model->page_section_id = (int)($model->page_section_id ?: 1);

                if ($model->save()) {
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            }
        } else {
            $model->loadDefaultValues();
            $model->group_id   = (int) self::userGroup();
            $model->page_section_id = (int)($model->page_section_id ?: 1);
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post())) {
            $model->page_section_id = (int)($model->page_section_id ?: 1);
            if ($model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', ['model' => $model]);
    }
    /**
     * Updates an existing Page model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */

    public function actionClone($id)
    {
        $model = new Page();

        if (!$this->request->isPost) {
            $model = $this->findModel($id);
        } else if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }


    /**
     * Deletes an existing Page model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $files = $model->getFiles()->all();
        foreach ($files as $file) {
            $ok = Yii::$app->storage->deleteById($id);
        }
        $this->findModel($id)->delete();
        return $this->redirect(['index']);
    }
}
