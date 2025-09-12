<?php

namespace croacworks\essentials\controllers;

use Yii;
use croacworks\essentials\models\Log;
use croacworks\essentials\models\LogSearch;
use yii\web\NotFoundHttpException;

/**
 * LogController implements the CRUD actions for Log model.
 */
class LogController extends AuthorizationController
{
    /**
     * Lists all Log models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new Log();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionAuth(): string
    {
        $q = \croacworks\essentials\models\Log::find()
            ->where(['action' => 'login'])
            ->orderBy(['id' => SORT_DESC]);

        // filtros simples via GET (?username=...&success=0|1)
        $req = \Yii::$app->request;
        $username = trim((string)$req->get('username', ''));
        $success  = $req->get('success', '');

        if ($username !== '') {
            // filtra pelo username dentro do JSON (data)
            // MySQL/MariaDB: usa LIKE simples no campo data
            $q->andWhere(['like', 'data', '"username":"' . addslashes($username) . '"', false]);
        }

        if ($success !== '' && ($success === '0' || $success === '1')) {
            $q->andWhere(['like', 'data', '"success":' . ($success === '1' ? 'true' : 'false'), false]);
        }

        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => $q,
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('login-logs', [
            'dataProvider' => $dataProvider,
            'username'     => $username,
            'success'      => $success,
        ]);
    }

    /**
     * Displays a single Log model.
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
     * Creates a new Log model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Log();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Log model.
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

    /**
     * Deletes an existing Log model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Log model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Log the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id, $model = null)
    {
        if (($model = Log::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
