<?php

namespace roacworks\essentials\controllers;

use Yii;
use croacworks\essentials\models\DynamicForm;
use croacworks\essentials\models\FormField;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * DynamicFormController implements the CRUD actions for DynamicForm model.
 */
class DynamicFormController extends  \croacworks\essentials\controllers\AuthorizationController
{

    /**
     * Lists all DynamicForm models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new DynamicForm();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, ['pageSize' => 10, 'orderBy' => ['id' => SORT_DESC], 'order' => false]);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single DynamicForm model.
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

    public function actionGetFields($class)
    {
        return parent::actionGetFields($class);
    }

    public function actionShow($id)
    {
        $this->layout = 'blank';
        return $this->render('show', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionSubmit($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $fields = FormField::find()
            ->where(['dynamic_form_id' => $id, 'status' => 1])
            ->orderBy(['order' => SORT_ASC])
            ->all();

        $postData = Yii::$app->request->post('DynamicModel'); // <- Aqui a mudança

        if (empty($postData)) {
            return ['success' => false, 'message' => 'Nenhum dado recebido.'];
        }

        $responseData = [];

        foreach ($fields as $field) {
            $name = $field->name;
            $value = $postData[$name] ?? null;

            if (is_array($value)) {
                $value = implode(',', $value);
            }

            $responseData[$name] = $value;
        }

        Yii::$app->db->createCommand()->insert('form_responses', [
            'dynamic_form_id' => $id,
            'response_data' => json_encode($responseData),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ])->execute();

        return ['success' => true, 'message' => 'Formulário enviado com sucesso.'];
    }

    /**
     * Creates a new DynamicForm model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new DynamicForm();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing DynamicForm model.
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
}
