<?php

namespace croacworks\essentials\controllers;

use croacworks\essentials\controllers\rest\StorageController;
use croacworks\essentials\models\Configuration;
use croacworks\essentials\models\Language;
use croacworks\essentials\models\Page;
use croacworks\essentials\models\PageSection;
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
                'section_id'  => $sectionId,
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
    public function actionPublic(string $slug, string $section = null, $lang = null, $group = 1, $modal = null)
    {
        $language = null;

        if ($modal && (int)$modal === 1) {
            $this->layout = 'main-blank';
        }

        $q = Page::find()
            ->andWhere(['pages.slug' => $slug])
            ->andWhere(['pages.status' => 1])
            ->andWhere(['group_id' => (int)$group]);
            

        $section = PageSection::findOne(['slug' => $section]);

        if ($section) {
            $q->andWhere(['page_section_id' => $section?->id]);
        } else { 
            $q->andWhere(['IS', 'page_section_id', null]);
        }

        if ($lang && ($language = Language::findOne(is_numeric($lang) ? (int)$lang : ['code' => $lang])) !== null) {
            $q->andWhere(['language_id' => $language->id]);
        } else {
            $query = $q;
            $query->andWhere(['IS', 'language_id', null]);
            
            if (!$query->one()) {
                $lang = Configuration::get()->language;
                $q->andWhere(['language_id' => $lang->id]);
            }
        }

        $model = $q->one();

        if (!$model) {
            throw new \yii\web\NotFoundHttpException(\Yii::t('app', 'Page not found or inactive.'));
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
