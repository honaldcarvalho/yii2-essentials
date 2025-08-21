<?php

namespace croacworks\essentials\controllers;

use croacworks\essentials\models\Menu;
use croacworks\essentials\models\Rule;
use croacworks\essentials\models\RuleSearch;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * RuleController implements the CRUD actions for Rule model.
 */
class RuleController extends AuthorizationController
{
    public function __construct($id, $module, $config = array()) {
        parent::__construct($id, $module, $config);
        $this->free = ['fix'];
    }
    /**
     * Lists all Rule models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new Rule();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Rule model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Rule model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Rule();

        if ($this->request->isPost) {
            $post = $this->request->post();

            if ($this->request->isPost && $model->load($post)) {
                if(isset($post['to'])){
                    $model->actions = implode(';',$post['to']);
                }
                $controller_parts = explode(':',$model->controller);
                $model->path = strtolower($controller_parts[0]);
                $model->controller = end($controller_parts);
                $model->actions = implode(';',$post['to']);
                
                $model->origin = isset($post['Rule']['origin']) ? implode(';',$post['Rule']['origin']) : '*';
            }

            if ($model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Rule model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        
        $model = $this->findModel($id);
        $post = $this->request->post();
        
        if ($this->request->isPost && $model->load($post)) {
            if(isset($post['to'])){
                $model->actions = implode(';',$post['to']);
            }
            $controller_parts = explode(':',$model->controller);
            $model->path = strtolower($controller_parts[0]);
            $model->controller = end($controller_parts);
            $model->actions = implode(';',$post['to']);
            
            $model->origin = isset($post['Rule']['origin']) ? implode(';',$post['Rule']['origin']) : '*';
        }

        if ($this->request->isPost && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionEdit($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('edit', [
            'model' => $model,
        ]);
    }


    /**
     * Deletes an existing Rule model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Rule model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Rule the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id,$model=null)
    {
        if (($model = Rule::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
