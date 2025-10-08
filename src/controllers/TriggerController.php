<?php
namespace croacworks\essentials\controllers;

use Yii;
use croacworks\essentials\models\Trigger;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;

class TriggerController extends AuthorizationController
{

    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Trigger::find()->orderBy(['id' => SORT_DESC]),
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionCreate()
    {
        $model = new Trigger();
        if ($model->load(Yii::$app->request->post()) && $model->save())
            return $this->redirect(['index']);
        return $this->render('form', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save())
            return $this->redirect(['index']);
        return $this->render('form', ['model' => $model]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        return $this->redirect(['index']);
    }

    public function actionLogs($id)
    {
        $model = $this->findModel($id);
        $dataProvider = new ActiveDataProvider([
            'query' => $model->getLogs(),
        ]);
        return $this->render('logs', ['model' => $model, 'dataProvider' => $dataProvider]);
    }

}
