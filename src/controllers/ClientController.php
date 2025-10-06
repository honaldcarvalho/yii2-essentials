<?php

namespace croacworks\essentials\controllers;

use Yii;
use croacworks\essentials\models\Client;
use croacworks\essentials\controllers\AuthorizationController;
use yii\web\NotFoundHttpException;

/**
 * ClientController implements the CRUD actions for Client model.
 */
class ClientController extends AuthorizationController
{
    /**
     * Lists all Client models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new Client(['scenario' => Client::SCENARIO_SEARCH]);
        $searchModel->verGroup = true;
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Client model.
     * @param int $id
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
     * Creates a new Client model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Client();
        if ($model->load(Yii::$app->request->post())) {
            
            $name_array = explode(' ', $model->fullname);
            $model->username = strtolower($name_array[0] . '_' . end($name_array)).'_'.Yii::$app->security->generateRandomString(8);
            $model->setPassword(Yii::$app->security->generateRandomString(6));
            $model->generateAuthKey();

            if ($model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }else{
                //dd($model->getErrors());
                \Yii::$app->session->setFlash('error', 'Erro: PACCTRCRE');
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Client model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->scenario = Client::SCENARIO_UPDATE;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Client model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

}
