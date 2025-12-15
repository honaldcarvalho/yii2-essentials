<?php

namespace croacworks\essentials\controllers;

use Yii;
use yii\base\DynamicModel;
use yii\helpers\StringHelper;
use yii\helpers\Url;

use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\models\FormField;
use croacworks\essentials\models\FormResponse;
use croacworks\essentials\models\Page;
use croacworks\essentials\traits\CloneActionTrait;

class PageController extends AuthorizationController
{
    use CloneActionTrait;

    public $classFQCN = Page::class;

    public function actionIndex()
    {
        $searchModel = new $this->classFQCN(['scenario' => $this->classFQCN::SCENARIO_SEARCH]);

        try {
            $searchModel->page_section_id = $this->classFQCN::sectionId();
        } catch (\Throwable $e) {
            Yii::$app->session->addFlash('danger', $e->getMessage());
        }

        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('/page/index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'class'        => $this->classFQCN,
            'model_name'   => StringHelper::basename($this->classFQCN)
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->render('/page/view', [
            'model'         => $model,
            'dynamicForm'   => $this->classFQCN::getDynamicForm(),
            'class'         => $this->classFQCN,
            'hasDynamic'    => $this->classFQCN::hasDynamic,
            'model_name'    => StringHelper::basename(get_class($model)),
        ]);
    }

    /**
     * Creates a new model.
     */
    public function actionCreate()
    {
        /** @var \croacworks\essentials\models\ModelCommon $model */
        $model = new ($this->classFQCN);

        // 1. Get Metadata Form
        $dynamicForm = $this->classFQCN::getDynamicForm();

        // 2. Build DynamicModel for Validation only (optional UI helper)
        $responseModel = $this->buildDynamicModel($dynamicForm->id ?? 0);

        if ($this->request->isPost) {
            $model->load($this->request->post());

            // Load dynamic data for validation check
            if ($dynamicForm) {
                $responseModel->load($this->request->post());
            }

            // Validate Main Model
            $isValid = $model->validate();

            // Validate Dynamic Fields (if applicable)
            if ($dynamicForm && isset($_POST['DynamicModel'])) {
                $isValid = $responseModel->validate() && $isValid;
            }

            if ($isValid) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    // A. Save Main Model
                    if (!$model->save(false)) {
                        throw new \Exception(Yii::t('app', 'Error saving record.'));
                    }

                    // B. Save Metadata
                    if ($dynamicForm && isset($_POST['DynamicModel'])) {
                        // Ensure instance exists and delegate logic to model
                        $formResponse = FormResponse::ensureForPage($dynamicForm->id, $model->id);
                        $formResponse->saveDynamicData($dynamicForm, $model->id, $this->request->post('DynamicModel', []));
                    }

                    $transaction->commit();
                    return $this->redirect(['view', 'id' => $model->id]);
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::error($e->getMessage(), __METHOD__);
                    $model->addError('slug', $e->getMessage());
                }
            }
        }

        // View switching logic
        $viewName = $dynamicForm ? '_form_meta' : '_form';

        return $this->render('/page/create', [
            'model' => $model,
            'model_name' => 'Course', // Ou dinâmico se preferir
            'dynamicForm' => $dynamicForm,
            'responseModel' => $responseModel, // Passado para preencher o form na view
            'viewName' => $viewName
        ]);
    }

    /**
     * Updates an existing model.
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $dynamicForm = $this->classFQCN::getDynamicForm();

        // 1. Find existing response to populate the form
        $formResponse = null;
        if ($dynamicForm) {
            $formResponse = FormResponse::findByJsonField('page_id', (string)$model->id, $dynamicForm->id);
        }

        $responseModel = $this->buildDynamicModel($dynamicForm->id ?? 0, $formResponse);

        if ($this->request->isPost) {
            $model->load($this->request->post());

            if ($dynamicForm) {
                $responseModel->load($this->request->post());
            }

            $isValid = $model->validate();
            if ($dynamicForm && isset($_POST['DynamicModel'])) {
                $isValid = $responseModel->validate() && $isValid;
            }

            if ($isValid) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    if (!$model->save(false)) {
                        throw new \Exception(Yii::t('app', 'Error saving record.'));
                    }

                    if ($dynamicForm && isset($_POST['DynamicModel'])) {
                        // Ensure instance exists (concurrent safety or if missing)
                        if (!$formResponse) {
                            $formResponse = FormResponse::ensureForPage($dynamicForm->id, $model->id);
                        }
                        $formResponse->saveDynamicData($dynamicForm, $model->id, $this->request->post('DynamicModel', []));
                    }

                    $transaction->commit();
                    return $this->redirect(['view', 'id' => $model->id]);
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::error($e->getMessage(), __METHOD__);
                    $model->addError('slug', $e->getMessage());
                }
            }
        }

        $viewName = $dynamicForm ? '_form_meta' : '_form';

        return $this->render('/page/update', [
            'model' => $model,
            'model_name' => 'Course',
            'dynamicForm' => $dynamicForm,
            'formResponse' => $formResponse,
            'viewName' => $viewName
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
        // 1. Prepare Clone Draft (Standard from Trait)
        /** @var Page $clone */
        $clone = $this->prepareCloneDraft($id, $this->classFQCN, $target_lang, $provider);

        // 2. Translate content if requested (Using Page model logic)
        if ($target_lang) {
            $clone->translateContent($target_lang, $provider);
        }

        // 3. Setup Dynamic Form and Data
        $dynamicForm = $this->classFQCN::getDynamicForm();

        // Find existing response from the SOURCE page ($id) to populate the clone form
        $sourceFormResponse = null;
        if ($dynamicForm) {
            $sourceFormResponse = FormResponse::findByJsonField('page_id', (string)$id, $dynamicForm->id);

            // Translate metadata content if target_lang is provided
            if ($sourceFormResponse && $target_lang) {
                $sourceFormResponse->translateContent($target_lang, $provider);
            }
        }

        // Build model with source data (translated if applicable)
        $responseModel = $this->buildDynamicModel($dynamicForm->id ?? 0, $sourceFormResponse);

        $submitUrl = Url::to(['clone', 'id' => $id, 'target_lang' => $target_lang, 'provider' => $provider]);

        if ($this->request->isPost) {
            $clone->load($this->request->post());

            if ($dynamicForm) {
                $responseModel->load($this->request->post());
            }

            $isValid = $clone->validate();
            if ($dynamicForm && isset($_POST['DynamicModel'])) {
                $isValid = $responseModel->validate() && $isValid;
            }

            if ($isValid) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    // Ensure correct section for the new page
                    $clone->page_section_id = $this->classFQCN::sectionId();

                    if (!$clone->save(false)) {
                        throw new \Exception(Yii::t('app', 'Error saving record.'));
                    }

                    if ($dynamicForm && isset($_POST['DynamicModel'])) {
                        // Ensure instance exists for the NEW page
                        $newFormResponse = FormResponse::ensureForPage($dynamicForm->id, $clone->id);
                        $newFormResponse->saveDynamicData($dynamicForm, $clone->id, $this->request->post('DynamicModel', []));
                    }

                    $transaction->commit();
                    return $this->redirect(['view', 'id' => $clone->id]);
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::error($e->getMessage(), __METHOD__);
                    $clone->addError('slug', $e->getMessage());
                }
            }
        }

        $viewName = $dynamicForm ? '_form_meta' : '_form';

        return $this->render('/page/clone', [
            'model'         => $clone,
            'formResponse'  => $sourceFormResponse,
            'dynamicForm'   => $dynamicForm,
            'model_name'    => StringHelper::basename(get_class($clone)),
            'submitUrl'     => $submitUrl,
            'viewName'      => $viewName,
        ]);
    }
    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    protected function buildDynamicModel(int $formId, $existingResponse = null): DynamicModel
    {
        $fields = FormField::find()
            ->where(['dynamic_form_id' => $formId, 'status' => 1])
            ->orderBy(['order' => SORT_ASC])
            ->all();

        $attributes = [];
        $data = [];

        // Decode existing JSON via Magic Method access (since FormResponse handles decoding in afterFind)
        if ($existingResponse) {
            // Using getData() legacy helper or direct access if desired
            $data = $existingResponse->getData();
        }

        foreach ($fields as $field) {
            $val = $data[$field->name] ?? $field->default ?? null;
            $attributes[$field->name] = $val;
        }

        $dynamicModel = new DynamicModel($attributes);

        foreach ($fields as $field) {
            $dynamicModel->addRule($field->name, 'safe');

            if ($field->show && isset($field->required) && $field->required) { // Assuming 'required' column exists or logic
                // Add specific validations if needed
                $dynamicModel->addRule($field->name, 'required');
            }
        }

        return $dynamicModel;
    }
}
