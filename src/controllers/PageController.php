<?php

namespace croacworks\essentials\controllers;

use croacworks\essentials\models\Configuration;
use croacworks\essentials\models\Language;
use croacworks\essentials\models\Page;
use croacworks\essentials\models\PageSection;
use croacworks\essentials\services\PageCloneService;
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
     * Displays public page.
     * @param string $slug
     * @param string|null $section
     * @param string|int|null $lang
     * @param int $group
     * @param int|null $modal
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
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
        $original = $this->findModel($id); // use o mesmo findModel() que você já tem

        // 1) GET -> mostra form de edição com um "rascunho" (id=null)
        if (!Yii::$app->request->isPost) {
            $draft = new Page();
            $draft->attributes = $original->attributes;

            // Forçar novos valores gerados no save (iguais ao Post)
            $draft->id = null;
            $draft->slug = null;                // deixa regenerar
            // $draft->model_group_id mantém/zera apenas no POST, conforme regra na detecção

            // Renderize a mesma view de edição (igual ao Post)
            return $this->render('update', [
                'model' => $draft,
                // passe outros parâmetros que sua view "update" espera (ex.: listas, providers, etc.)
                'isClone' => true, // flag opcional caso sua view trate rótulos/botões
                'originalId' => $original->id,
            ]);
        }

        // 2) POST -> usuário confirmou; carregar dados enviados e decidir o tipo de clone
        $posted = Yii::$app->request->post();

        // Carregue em um modelo "buffer" apenas para comparar campos relevantes e capturar overrides
        $buffer = new Page();
        $buffer->load($posted); // o índice ('Page' ou outro) deve bater com o name do form

        // Monte os overrides a partir do buffer (sem suposições de campos específicos — copiamos tudo)
        // OBS: se você precisa excluir alguns atributos controlados internamente, faça como no Post.
        $overrides = $buffer->attributes;

        // DETECÇÃO (espelhando o Post): 
        // - Clone de idioma quando o usuário alterou apenas a língua (mantém o mesmo model_group_id)
        // - Clone total quando for para "duplicar" para outro grupo (novo group) ou quando explicitamente marcado
        //
        // Se no seu Post há um campo/botão que força o modo, espelhe aqui:
        $forceTotal = (bool)($posted['clone_mode'] ?? false) && $posted['clone_mode'] === 'total';
        $forceLang  = (bool)($posted['clone_mode'] ?? false) && $posted['clone_mode'] === 'language';

        // Heurística alinhada ao padrão do Post:
        // - se explicitamente indicado no form, obedecer
        // - senão, se a linguagem mudou e não há intenção explícita de novo grupo => cloneLanguage
        // - caso contrário => cloneTotal
        $languageChanged = (string)$buffer->language_id !== (string)$original->language_id;

        // Importante: não fixamos model_group_id aqui — quem define é o serviço (como no Post)
        // cloneLanguage: mantém group do original; cloneTotal: inicia novo group.

        if ($forceLang || ($languageChanged && !$forceTotal)) {
            $clone = PageCloneService::cloneLanguage($original, $overrides);
        } else {
            $clone = PageCloneService::cloneTotal($original, $overrides);
        }

        Yii::$app->session->addFlash('success', Yii::t('app', 'Página clonada com sucesso.'));
        // Redirecione para a mesma rota que o Post usa após clonar (geralmente update/view do clone)
        return $this->redirect(['view', 'id' => $clone->id]);
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

        try {
            PageCloneService::deletePage($model);
            Yii::$app->session->addFlash('success', Yii::t('app', 'Post deleted.'));
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
            Yii::$app->session->addFlash('danger', Yii::t('app', 'Failed to delete post.'));
        }

        return $this->redirect(['index']);
    }
}
