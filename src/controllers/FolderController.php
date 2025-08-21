<?php

namespace croacworks\essentials\controllers;

use croacworks\essentials\models\File;
use Yii;
use croacworks\essentials\models\Folder;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;

/**
 * FolderController implements the CRUD actions for Folder model.
 */
class FolderController extends AuthorizationController
{

    /**
     * Lists all Folder models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new Folder();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $model_folder = new Folder();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'model_folder' => $model_folder,
        ]);
    }

    /**
     * Displays a single Folder model.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        $dataProviderFolders = new ActiveDataProvider([
            'query' => $model->getFolders()
        ]);

        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => File::find()->andFilterWhere(['folder_id'=>$model->id])
            ->andWhere(['or',['in','group_id',AuthorizationController::getUserGroups()],['group_id'=>null], ['group_id'=>1]]),
        ]);
        
        return $this->render('view', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'dataProviderFolders' => $dataProviderFolders,
        ]);
    }

    /**
     * Displays a single Folder model.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionShow($id)
    {
        return $this->render('show', [
            'model' => $this->findModel($id),
        ]);
    }
    /**
     * Creates a new Folder model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Folder();

        if ($model->load(Yii::$app->request->post()) && ($model->group_id = $this::getUserGroups()[0]) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionAdd()
    {
        $model = new Folder();

        if ($model->load(Yii::$app->request->post()) && ($model->group_id = $this::getUserGroups()[0]) && $model->save()) {
            return true;
        }

        return false;
    }

    /**
     * Updates an existing Folder model.
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
     * Updates an existing Folder model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionEdit($id)
    {
        $this->layout ='partial';
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return true;
        }

        return $this->render('edit', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Folder model.
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

    public function actionRemove($id)
    {
        return Folder::find()->where(['id'=>$id])->andWhere(['or',['in','group_id',AuthorizationController::getUserGroups()]])->one()->delete();
    }

}
