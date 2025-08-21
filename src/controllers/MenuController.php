<?php

namespace croacworks\essentials\controllers;

use Yii;
use croacworks\essentials\models\Menu;
use croacworks\essentials\models\MenuSearch;
use yii\helpers\Inflector;
use yii\web\NotFoundHttpException;

/**
 * MenuController implements the CRUD actions for Menu model.
 */
class MenuController extends AuthorizationController
{
    /**
     * Lists all Menu models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new Menu();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Menu model.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $searchModel = new Menu();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, $id);
        return $this->render('view', [
            'model' => $this->findModel($id),
            'dataProvider' => $dataProvider
        ]);
    }

    /**
     * Creates a new Menu model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($id = null)
    {
        $model = new Menu();

        if ($model->load(Yii::$app->request->post())) {
            $maxId = Menu::find()->max('id');
            $id =  $maxId + 1;
            $model->id = $id;

            if ($model->save()) {
                if (!empty($model->menu_id) && $model->menu_id !== null) {
                    return $this->redirect(['view', 'id' => $model->menu_id]);
                }
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        $model->menu_id = $id;

        return $this->render('create', [
            'model' => $model,
        ]);
    }


    public function actionAdd($id = null)
    {
        $model = new Menu();

        if ($model->load(Yii::$app->request->post())) {
            $maxId = Menu::find()->max('id');
            $id =  $maxId + 1;
            $model->id = $id;

            if ($model->save()) {
                if (!empty($model->menu_id) && $model->menu_id !== null) {
                    return $this->redirect(['view', 'id' => $model->menu_id]);
                }
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        $model->menu_id = $id;

        return $this->render('add', [
            'model' => $model,
        ]);
    }

    public function actionAutoAdd($controller, $action = 'index')
    {
        if (!$controller || !class_exists($controller)) {
            throw new \yii\web\NotFoundHttpException("Controller inválido: $controller");
        }

        // Verifica duplicata
        $exists = Menu::find()
            ->where(['controller' => $controller, 'action' => $action])
            ->exists();

        if ($exists) {
            Yii::$app->session->setFlash('warning', "Já existe um menu para <code>$controller::$action</code>.");
            return $this->redirect(['index']);
        }

        // Extrai o nome base do controller e converte para ID
        $reflection = new \ReflectionClass($controller);
        $baseName = preg_replace('/Controller$/', '', $reflection->getShortName()); // ex: FormResponse
        $controllerId = Inflector::camel2id($baseName); // ex: form-response

        // Define o caminho base (ex: app, backend, etc)
        $namespaceParts = explode('\\', $controller);
        $path = isset($namespaceParts[0]) ? strtolower($namespaceParts[0]) : 'app';

        // Cria novo item de menu
        $model = new Menu();
        $model->label = Inflector::camel2words($baseName);              // Ex: Form Response
        $model->controller = $controller;
        $model->action = $action;
        $model->visible = "$controller;$action";
        $model->url = "/$controllerId/$action";
        $model->icon = 'fas fa-circle';
        $model->icon_style = 'fas';
        $model->path = $path;
        $model->active = $controllerId;
        $model->order = (Menu::find()->max('`order`') ?? 0) + 1;
        $model->status = 1;

        if ($model->save()) {
            Yii::$app->session->setFlash('success', "Menu para <code>$controller::$action</code> criado com sucesso.");
            return $this->redirect(['view', 'id' => $model->id]);
        }

        Yii::$app->session->setFlash('error', "Erro ao criar menu.");
        return $this->redirect(['index']);
    }


    /**
     * Updates an existing Menu model.
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
     * Deletes an existing Menu model.
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

    public function actionOrderMenu()
    {
        $menus = [];

        if (Yii::$app->request->isPost) {

            $menus = $_POST['items'];

            foreach ($menus as $key => $value) {
                $rst = Yii::$app->db->createCommand()->update('menus', ['order' => $key + 1], "id = {$value}")->execute();
                echo $rst;
            }
        }
        return \yii\helpers\Json::encode(['atualizado' => $menus]);
    }

    /**
     * Finds the Menu model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Menu the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id, $model = null)
    {
        if (($model = Menu::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
