<?php

namespace croacworks\essentials\controllers;

use croacworks\essentials\models\Configuration;
use croacworks\essentials\models\Language;
use croacworks\essentials\models\Page;
use croacworks\essentials\models\PageSection;
use croacworks\essentials\services\PageCloneService;
use croacworks\essentials\traits\CloneActionTrait;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * CRUD for Page model.
 */
class PageController extends AuthorizationController
{
    use CloneActionTrait;

    /** Actions that do not require auth */
    public $free = ['login', 'signup', 'error', 'public'];

    /** List pages with search scenario */
    public function actionIndex()
    {
        $searchModel = new Page();
        $searchModel->scenario = Page::SCENARIO_SEARCH;
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', compact('searchModel', 'dataProvider'));
    }

    /** Show a single page */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        return $this->render('view', compact('model'));
    }

    /**
     * Find active page by composite key (throws 404 if missing/inactive).
     */
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

    /**
     * Public "show" by slug + context (group/language/section).
     */
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

        $groupId    = $group ?? (self::isGuest() ? 1 : (int) self::userGroup());
        $sectionId  = (int)($section ?: 1);
        $languageId = (int)$language;

        $model = $this->findByKey($page, $groupId, $languageId, $sectionId);
        return $this->render('page', compact('model'));
    }

    /**
     * Public page resolver.
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
            $q->andWhere(['page_section_id' => $section->id]);
        } else {
            $q->andWhere(['IS', 'page_section_id', null]);
        }

        if ($lang && ($language = Language::findOne(is_numeric($lang) ? (int)$lang : ['code' => $lang])) !== null) {
            $q->andWhere(['language_id' => $language->id]);
        } else {
            // prefer language_id IS NULL, otherwise default configured language
            $query = $q;
            $query->andWhere(['IS', 'language_id', null]);
            if (!$query->one()) {
                $lang = Configuration::get()->language;
                $q->andWhere(['language_id' => $lang->id]);
            }
        }

        $model = $q->one();
        if (!$model) {
            throw new NotFoundHttpException(Yii::t('app', 'Page not found or inactive.'));
        }

        return $this->render('page', compact('model'));
    }

    /** Create page (auto-assign current user group) */
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

        return $this->render('create', compact('model'));
    }

    /** Update page */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', compact('model'));
    }

    /**
     * Clone workflow with optional auto-translation.
     * Uses CloneActionTrait for shared logic.
     *
     * @param int $id
     * @param string|null $target_lang
     * @param string $provider
     * @return string|\yii\web\Response
     */
    public function actionClone($id, $target_lang = null, $provider = 'default')
    {
        // 1. Prepare draft using Trait
        $clone = $this->prepareCloneDraft($id, Page::class, $target_lang, $provider);

        // 2. Handle POST (Save) using Trait
        if (Yii::$app->request->isPost && $clone->load(Yii::$app->request->post())) {

            $newPage = $this->processCloneSave($id, $clone, Page::class);

            if ($newPage) {
                return $this->redirect(['view', 'id' => $newPage->id]);
            }
        }

        // 3. Render view (Controller specific)
        return $this->render('clone', [
            'model' => $clone,
        ]);
    }

    /** Delete page via service (handles linked data) */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        try {
            PageCloneService::deletePage($model);
            Yii::$app->session->addFlash('success', Yii::t('app', 'Page deleted.'));
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
            Yii::$app->session->addFlash('danger', Yii::t('app', 'Failed to delete page.'));
        }

        return $this->redirect(['index']);
    }
}
