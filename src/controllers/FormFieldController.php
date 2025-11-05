<?php

namespace app\controllers;

use Yii;
use croacworks\essentials\models\FormField;
use croacworks\essentials\models\AgendaSearch;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * FormFieldController implements the CRUD actions for FormField model.
 */
class FormFieldController extends  \croacworks\essentials\controllers\AuthorizationController
{

    /**
     * Lists all FormField models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new FormField();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams,['pageSize'=>10, 'orderBy'=>['id' => SORT_DESC],'order'=>false]);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single FormField model.
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
     * Creates a new FormField model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new FormField();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing FormField model.
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
