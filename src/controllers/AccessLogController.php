<?php

namespace croacworks\essentials\controllers;

use croacworks\essentials\models\AccessLog;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use yii\web\NotFoundHttpException;

/**
 * AccessLogController implements the CRUD actions for AccessLog model.
 */
class AccessLogController extends AuthorizationController
{

    public function actionIndex()
    {
        $request = Yii::$app->request;
        $searchModel = new AccessLog();
        // Captura as datas escolhidas pelo usuário
        $startDate = $request->get('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = $request->get('end_date', date('Y-m-d'));

        // Query para contar acessos por URL no período selecionado
        $query = (new Query())
            ->from('access_log')
            ->select(['url', 'COUNT(*) as total_access'])
            ->where(['between', 'created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->groupBy('url')
            ->orderBy(['total_access' => SORT_DESC]);

        // DataProvider para o GridView
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 10],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'searchModel' => $searchModel ,
            
        ]);
    }

    /**
     * Displays a single AccessLog model.
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
     * Creates a new AccessLog model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new AccessLog();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing AccessLog model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id
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
