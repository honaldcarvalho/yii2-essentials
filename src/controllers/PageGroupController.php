<?php

namespace croacworks\essentials\controllers;

use Yii;

use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\controllers\FormResponseController;
use croacworks\essentials\models\DynamicForm;
use croacworks\essentials\models\FormResponse;
use croacworks\essentials\models\Page;
use croacworks\essentials\traits\CloneActionTrait;
use yii\db\Transaction;
use yii\helpers\StringHelper;
use yii\helpers\Url;

class PageGroupController extends AuthorizationController
{
    use CloneActionTrait;

    public $form_name = 'page_form';
    public $classFQCN = Page::class;
    public $formResponseCtrl;

    public function init()
    {
        parent::init();
        if ($this->classFQCN::hasDynamic) {
            $this->formResponseCtrl = new FormResponseController('form-response', Yii::$app, [
                'form_name' => $this->form_name
            ]);
        }
    }

    public function actionIndex()
    {
        $searchModel = new $this->classFQCN(['scenario' => $this->classFQCN::SCENARIO_SEARCH]);

        try {
            $searchModel->page_section_id = $this->classFQCN::sectionId();
        } catch (\Throwable $e) {
            Yii::$app->session->addFlash('danger', $e->getMessage());
        }

        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('@essentials/views/page/index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'class'        => $this->classFQCN,
            'model_name'   => StringHelper::basename($this->classFQCN)
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
        $resp = $formId = $submitUrl = null;

        if ($this->classFQCN::hasDynamic)
            [$resp, $formId, $submitUrl] = $this->preparePageMeta($id);

        return $this->render('@essentials/views/page/view', [
            'model'         => $model,
            'formResponse'  => $resp,
            'dynamicFormId' => $formId,
            'submitUrl'     => $submitUrl,
            'class'         => $this->classFQCN,
            'hasDynamic'    => $this->classFQCN::hasDynamic,
            'model_name'    => StringHelper::basename(get_class($model)),
        ]);
    }

    public function actionCreate()
    {
        $model = new $this->classFQCN();
        $tx = Yii::$app->db->beginTransaction(Transaction::SERIALIZABLE);

        try {
            if ($model->load($this->request->post())) {
                $model->page_section_id = $this->classFQCN::sectionId();
                if ($model->save()) {
                    if ($this->classFQCN::hasDynamic) {
                        $form = $this->getDynamicForm();
                        if ($form) {
                            $req = Yii::$app->request;
                            $post = $req->post('DynamicModel', []);

                            // Inject page_id into metadata POST
                            $post['page_id'] = $model->id;
                            $req->setBodyParams(array_merge($req->bodyParams, ['DynamicModel' => $post]));

                            // Call createJson with form ID (int)
                            $result = $this->formResponseCtrl->createJson((int)$form->id);

                            if (!$result['success']) {
                                throw new \RuntimeException($result['error'] ?? 'Failed to save metadata.');
                            }
                        }
                    }
                    $tx->commit();
                    Yii::$app->session->addFlash('success', Yii::t('app', 'Page created.'));
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            }
        } catch (\Throwable $e) {
            if ($tx->isActive) $tx->rollBack();
            Yii::$app->session->addFlash('danger', $e->getMessage());
        }

        return $this->render('@essentials/views/page/create', [
            'model'        => $model,
            'model_name'   => StringHelper::basename(get_class($model)),
            'dynamicForm'  => $this->getDynamicForm(),
            'hasDynamic'    => $this->classFQCN::hasDynamic,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $resp = $formId = $submitUrl = null;

        if ($this->classFQCN::hasDynamic)
            [$resp, $formId, $submitUrl] = $this->preparePageMeta($id);

        if (Yii::$app->request->isPost) {
            $req = Yii::$app->request;

            // Update metadata if exists in POST
            if ($req->post('DynamicModel')) {
                $post = $req->post('DynamicModel', []);
                $post['page_id'] = $model->id;
                $req->setBodyParams(array_merge($req->bodyParams, ['DynamicModel' => $post]));

                $result = $this->formResponseCtrl->updateJson($resp);

                if (!$result['success']) {
                    Yii::$app->session->addFlash('danger', $result['error'] ?? 'Metadata update failed.');
                }
            }

            if ($model->load($req->post())) {
                $model->page_section_id = $this->classFQCN::sectionId();
                if ($model->save()) {
                    Yii::$app->session->addFlash('success', Yii::t('app', 'Data updated.'));
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            }
        }

        return $this->render('@essentials/views/page/update', [
            'model'         => $model,
            'responseModel' => $resp,
            'dynamicForm'   => $this->getDynamicForm(),
            'model_name'    => StringHelper::basename(get_class($model)),
            'dynamicFormId' => $formId,
            'submitUrl'     => $submitUrl,
            'hasDynamic'    => $this->classFQCN::hasDynamic,
        ]);
    }

    /**
     * Clone workflow using CloneActionTrait.
     * Includes specific logic for Page metadata handling.
     *
     * @param int $id
     * @param string|null $target_lang
     * @param string $provider
     * @return string|\yii\web\Response
     */
    public function actionClone($id, $target_lang = null, $provider = 'default')
    {
        $clone = $this->prepareCloneDraft($id, $this->classFQCN, $target_lang, $provider);

        // Load existing metadata
        [$resp, $formId, $originalUrl] = $this->preparePageMeta($id);

        // Translate metadata content if target_lang is provided
        if ($target_lang && $resp) {
            $resp->translateContent($target_lang, $provider);
        }

        $hasDynamic = $this->classFQCN::hasDynamic ?? false;

        // Force submit to clone action
        $submitUrl = Url::to(['clone', 'id' => $id]);

        $dynamicForm = $hasDynamic ? $this->getDynamicForm() : null;
        if (!$formId && $dynamicForm) {
            $formId = $dynamicForm->id;
        }

        if (Yii::$app->request->isPost) {
            if ($clone->load(Yii::$app->request->post())) {
                $clone->page_section_id = $this->classFQCN::sectionId();

                $newPage = $this->processCloneSave($id, $clone, $this->classFQCN);

                if ($newPage) {
                    if ($hasDynamic && $dynamicForm) {
                        $req = Yii::$app->request;
                        $post = $req->post('DynamicModel', []);

                        $post['page_id'] = $newPage->id;
                        $req->setBodyParams(array_merge($req->bodyParams, ['DynamicModel' => $post]));

                        $this->formResponseCtrl->createJson((int)$formId);
                    }

                    return $this->redirect(['view', 'id' => $newPage->id]);
                }
            }
        }

        return $this->render('@essentials/views/page/clone', [
            'model'         => $clone,
            'responseModel' => $resp,
            'dynamicForm'   => $dynamicForm,
            'model_name'    => StringHelper::basename(get_class($clone)),
            'dynamicFormId' => $formId,
            'submitUrl'     => $submitUrl,
            'hasDynamic'    => $hasDynamic,
        ]);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        try {
            if ($this->classFQCN::hasDynamic) {
                $this->deleteFormResponseByPageId($id);
            }
            $model->delete();
            Yii::$app->session->addFlash('success', Yii::t('app', 'Page deleted.'));
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
            Yii::$app->session->addFlash('danger', Yii::t('app', 'Failed to delete page.'));
        }

        return $this->redirect(['index']);
    }

    /**
     * Delete all FormResponse entries that reference the given page_id.
     */
    private function deleteFormResponseByPageId(int $pageId): void
    {
        $form = $this->getDynamicForm();
        if (!$form) {
            return;
        }

        $responses = FormResponse::find()
            ->where(['dynamic_form_id' => $form->id])
            ->andWhere(['JSON_EXTRACT(response_data, "$.page_id")' => (string)$pageId])
            ->all();

        foreach ($responses as $response) {
            try {
                $response->delete();
            } catch (\Throwable $e) {
                Yii::warning("Failed to delete FormResponse ID {$response->id}: {$e->getMessage()}", __METHOD__);
            }
        }
    }

    protected function getDynamicForm(): ?DynamicForm
    {
        return DynamicForm::findOne(['name' => $this->form_name]);
    }

    protected function preparePageMeta(int $pageId): array
    {
        $form = $this->getDynamicForm();
        if (!$form) {
            throw new \RuntimeException('Dynamic form not found.');
        }

        $resp = $this->findFormResponseByPageId($form->id, $pageId);
        if (!$resp) {
            $resp = new FormResponse([
                'dynamic_form_id' => $form->id,
                'response_data'   => ['page_id' => $pageId],
            ]);
            $resp->save(false);
        }

        return [$resp, (int)$form->id, ['form-response/update', 'id' => $resp->id]];
    }

    private function findFormResponseByPageId(int $formId, int $pageId): ?FormResponse
    {
        return FormResponse::find()
            ->where(['dynamic_form_id' => $formId])
            ->andWhere(['JSON_EXTRACT(response_data, "$.page_id")' => (string)$pageId])
            ->one();
    }
}
