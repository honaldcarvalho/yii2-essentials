<?php

namespace croacworks\essentials\controllers;

use Yii;
use yii\base\DynamicModel;
use yii\web\NotFoundHttpException;
use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\models\DynamicForm;
use croacworks\essentials\models\FormField;
use croacworks\essentials\models\FormResponse;

class FormResponseController extends AuthorizationController
{
    /** @var string Form name to operate on (must match DynamicForm->name) */
    public string $form_name = 'course_form';
    public string $model_name = 'course_form';

    protected DynamicForm $formDef;

    public function init()
    {
        parent::init();
        $this->formDef = $this->findFormByName($this->form_name);
    }

    public function actionIndex()
    {
        $searchModel = new FormResponse([
            'dynamic_form_id' => (int)$this->formDef->id,
        ]);

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // Ensure we only see responses for this specific form definition
        $dataProvider->query->andWhere(['dynamic_form_id' => (int)$this->formDef->id]);

        return $this->render('/form-response/index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'formDef'      => $this->formDef,
            'model_name'   => $this->model_name,
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);

        // We might need to build the dynamic model or pass fields to view to render labels correctly
        $responseModel = $this->buildDynamicModel($this->formDef->id, $model);

        return $this->render('/form-response/view', [
            'model'         => $model,
            'formDef'       => $this->formDef,
            'model_name'    => $this->model_name,
            'responseModel' => $responseModel, // Helps in the view to iterate attributes
        ]);
    }

    public function actionCreate()
    {
        $model = new FormResponse([
            'dynamic_form_id' => $this->formDef->id,
        ]);

        // Build validation model based on FormFields
        $responseModel = $this->buildDynamicModel($this->formDef->id);

        if ($this->request->isPost) {
            $postData = $this->request->post('DynamicModel', []);

            // Load data into validation model
            $responseModel->load($this->request->post());

            if ($responseModel->validate()) {
                try {
                    // Delegated logic to the model as requested
                    // Passing 0 as ownerId since this is a standalone controller, not attached to a Page
                    $model->saveDynamicData($this->formDef, 0, $postData);

                    Yii::$app->session->setFlash('success', Yii::t('app', 'Record created successfully.'));
                    return $this->redirect(['view', 'id' => $model->id]);
                } catch (\Exception $e) {
                    Yii::error($e->getMessage(), __METHOD__);
                    Yii::$app->session->setFlash('error', Yii::t('app', 'Error saving record.'));
                }
            }
        }

        return $this->render('/form-response/create', [
            'model'         => $model,
            'formDef'       => $this->formDef,
            'model_name'    => $this->model_name,
            'responseModel' => $responseModel,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        // Build validation model populated with existing JSON data
        $responseModel = $this->buildDynamicModel($this->formDef->id, $model);

        if ($this->request->isPost) {
            $postData = $this->request->post('DynamicModel', []);

            $responseModel->load($this->request->post());

            if ($responseModel->validate()) {
                try {
                    // Update logic using the model method
                    $model->saveDynamicData($this->formDef, 0, $postData);

                    Yii::$app->session->setFlash('success', Yii::t('app', 'Record updated successfully.'));
                    return $this->redirect(['view', 'id' => $model->id]);
                } catch (\Exception $e) {
                    Yii::error($e->getMessage(), __METHOD__);
                    Yii::$app->session->setFlash('error', Yii::t('app', 'Error saving record.'));
                }
            }
        }

        return $this->render('/form-response/update', [
            'model'         => $model,
            'formDef'       => $this->formDef,
            'model_name'    => $this->model_name,
            'responseModel' => $responseModel,
        ]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        Yii::$app->session->setFlash('success', Yii::t('app', 'Record deleted successfully.'));

        return $this->redirect(['index']);
    }

    // -------------------------------------------------------------------------
    //  PROTECTED HELPERS
    // -------------------------------------------------------------------------

    protected function findModel($id, $model = null): FormResponse
    {
        $model = FormResponse::find()
            ->where(['id' => $id, 'dynamic_form_id' => (int)$this->formDef->id])
            ->one();

        if (!$model) {
            throw new NotFoundHttpException(Yii::t('app', 'The requested record does not exist.'));
        }
        return $model;
    }

    protected function findFormByName(string $name): DynamicForm
    {
        $f = DynamicForm::find()->where(['name' => $name])->one();
        if (!$f) {
            throw new NotFoundHttpException(Yii::t('app', 'Dynamic form not found: {name}', ['name' => $name]));
        }
        return $f;
    }

    /**
     * Builds a DynamicModel for validation and form generation.
     * * @param int $formId
     * @param FormResponse|null $existingResponse
     * @return DynamicModel
     */
    protected function buildDynamicModel(int $formId, ?FormResponse $existingResponse = null): DynamicModel
    {
        $fields = FormField::find()
            ->where(['dynamic_form_id' => $formId, 'status' => 1])
            ->orderBy(['order' => SORT_ASC])
            ->all();

        $attributes = [];
        $data = [];

        if ($existingResponse) {
            $data = $existingResponse->getData();
        }

        foreach ($fields as $field) {
            $val = $data[$field->name] ?? $field->default ?? null;
            $attributes[$field->name] = $val;
        }

        $dynamicModel = new DynamicModel($attributes);

        foreach ($fields as $field) {
            // General safe rule
            $dynamicModel->addRule($field->name, 'safe');

            // Add required validation if applicable
            if ($field->show && !empty($field->required)) {
                $dynamicModel->addRule($field->name, 'required');
            }
        }

        return $dynamicModel;
    }
}
