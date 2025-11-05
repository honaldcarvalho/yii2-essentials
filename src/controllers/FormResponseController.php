<?php

namespace croacworks\essentials\controllers;

use croacworks\essentials\enums\FormFieldType;
use Yii;
use croacworks\essentials\models\FormResponse;
use croacworks\essentials\models\FormField;
use yii\web\NotFoundHttpException;

/**
 * FormResponseController implements the CRUD actions for FormResponse model.
 */
class FormResponseController extends  \croacworks\essentials\controllers\AuthorizationController
{

    /**
     * Lists all FormResponse models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new FormResponse();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, ['pageSize' => 10, 'orderBy' => ['id' => SORT_DESC], 'order' => false]);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single FormResponse model.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new FormResponse model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new FormResponse();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing FormResponse model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionUpdateJson($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $model = FormResponse::findOne($id);
        if (!$model) {
            return ['success' => false, 'error' => 'Resposta não encontrada'];
        }

        $postData = Yii::$app->request->post('DynamicModel', []);
        $casted = [];

        // Pega os tipos dos campos da fila
        $fields = FormField::find()
            ->where(['dynamic_form_id' => $model->dynamic_form_id]) // ou ajuste se o nome for dynamic_form_id
            ->indexBy('name')
            ->all();

        foreach ($postData as $name => $value) {
            $field = $fields[$name] ?? null;

            if ($value === '') {
                $casted[$name] = null;
                continue;
            }

            if (!$field) {
                $casted[$name] = $value;
                continue;
            }

            switch ((int) $field->type) {
                case FormFieldType::TYPE_NUMBER:
                    $casted[$name] = is_numeric($value) ? $value + 0 : null;
                    break;

                case FormFieldType::TYPE_CHECKBOX:
                case FormFieldType::TYPE_MULTIPLE:
                    $casted[$name] = (array)$value;
                    break;

                case FormFieldType::TYPE_DATE:
                case FormFieldType::TYPE_DATETIME:
                    $casted[$name] = date('Y-m-d H:i:s', strtotime($value));
                    break;

                case FormFieldType::TYPE_PHONE:
                case FormFieldType::TYPE_IDENTIFIER:
                case FormFieldType::TYPE_TEXT:
                case FormFieldType::TYPE_TEXTAREA:
                case FormFieldType::TYPE_SELECT:
                case FormFieldType::TYPE_SQL:
                case FormFieldType::TYPE_MODEL:
                case FormFieldType::TYPE_EMAIL:
                default:
                    $casted[$name] = $value;
                    break;
            }
        }

        $model->response_data = $casted;

        if ($model->save(false)) {
            return ['success' => true, 'message' => 'Dados atualizados com sucesso'];
        }

        return ['success' => false, 'error' => 'Erro ao salvar dados'];
    }

    public function actionEdit($id)
    {
        $model = FormResponse::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('Resposta não encontrada.');
        }

        return $this->renderAjax('_form_widget', [
            'model' => $model,
        ]);
    }
}
