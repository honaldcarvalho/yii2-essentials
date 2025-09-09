<?php

namespace croacworks\essentials\controllers;

use croacworks\essentials\controllers\rest\StorageController;
use croacworks\essentials\models\Language;
use croacworks\essentials\models\Page;
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

    public function actionShow($page, $language = 2, $modal = null)
    {
        $model = $this->findModel(['slug' => $page, 'language_id' => $language]);

        if ($modal && (int)$modal === 1) {
            $this->layout = 'main-blank';
        }

        return $this->render('page', ['model' => $model]);
    }

    /**
     * Action PÚBLICA para servir páginas por slug + linguagem + grupo (opcional).
     * URL exemplos:
     *   /page/public?slug=home&lang=pt-BR&group=12
     *   /p/12/pt-BR/home
     *
     * @param string      $slug   Slug da página
     * @param string|int  $lang   ID numérico da language OU código/locale (ex.: 'pt-BR', 'en', 'pt')
     * @param int|null    $group  ID do grupo (opcional). Se omitido, não filtra por grupo.
     * @param int|null    $modal  Se 1, usa layout 'main-blank'
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionPublic(string $slug, $lang = null, $group = 1, $modal = null)
    {
        if ($modal && (int)$modal === 1) {
            $this->layout = 'main-blank';
        }

        $q = Page::find()->alias('p')
            ->andWhere(['p.slug' => $slug])
            ->andWhere(['p.status' => 1]);

        // group padrão = 1 (coringa)
        $q->andWhere(['p.group_id' => (int)$group]);

        if ($lang !== null && $lang !== '') {
            if (is_numeric($lang)) {
                $q->andWhere(['p.language_id' => (int)$lang]);
            } else {
                $langTable = \croacworks\essentials\models\Language::tableName();
                $q->innerJoin("$langTable l", 'l.id = p.language_id')
                    ->andWhere(['or', ['l.code' => (string)$lang], ['l.locale' => (string)$lang]]);
            }
        }

        $model = $q->one();
        if (!$model) {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', 'Page not found or inactive.'));
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
            if ($model->load($this->request->post()) && ($model->group_id = $this::userGroup()) && $model->save()) {
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
     * Updates an existing Page model.
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
