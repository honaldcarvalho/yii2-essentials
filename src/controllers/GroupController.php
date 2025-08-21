<?php

namespace croacworks\essentials\controllers;

use croacworks\essentials\models\Group;
use croacworks\essentials\models\UserGroup;
use Mpdf\Tag\A;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * GroupController implements the CRUD actions for Group model.
 */
class GroupController extends AuthorizationController
{

    /**
     * Lists all Group models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new Group();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Group model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $users = new \yii\data\ActiveDataProvider([
            'query' => UserGroup::find()->where(['group_id' => $id]),
            'pagination' => false,
        ]);

        return $this->render('view', [
            'model' => $this->findModel($id),
            'users' => $users
        ]);
    }

    /**
     * Creates a new Group model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Group();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
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
     * Updates an existing Group model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Group model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionClone($id)
    {
        $message = "Erro ao clonar grupo.";
        $group = $this->findModel($id);
        try {
            $result = Group::cloneGroupWithRules($id, 'Clone of ' . $group->name);
            if ($result['success']) {
                $message = "Grupo clonado com sucesso: " . $result['group']->id;
                \Yii::$app->session->setFlash('success', $message);
                return $this->redirect(['index']);
            } else {
                $message = $result['message'];
                \Yii::$app->session->setFlash('danger', $message);
                return $this->redirect(['view', 'id' => $id]);
            }
        } catch (\Throwable $th) {
            \Yii::$app->session->setFlash('danger', $message);
            return $this->redirect(['view', 'id' => $id]);
        }
    }

    /**
     * Deletes an existing Group model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $group = $this->findModel($id);

            // Exclui as regras associadas
            foreach ($group->rules as $rule) {
                $rule->delete();
            }

            // Exclui os vínculos com usuários
            foreach ($group->userGroups as $userGroup) {
                $userGroup->delete();
            }

            // Exclui o grupo
            $group->delete();

            $transaction->commit();
            Yii::$app->session->setFlash('success', 'Group deleted');
            return $this->redirect(['index']);
        } catch (\Throwable $th) {
            $transaction->rollBack();

            $message = '';
            if (isset($th->errorInfo[0]) && $th->errorInfo[0] == "23000") {
                $message = Yii::t('app', 'In use!');
            }

            Yii::$app->session->setFlash('danger', "Can't delete group. {$message}");
            return $this->redirect(['view', 'id' => $id]);
        }
    }


    /**
     * Finds the Group model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Group the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id, $model = null)
    {
        if (($model = Group::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
