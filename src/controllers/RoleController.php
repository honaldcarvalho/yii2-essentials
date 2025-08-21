<?php

namespace croacworks\essentials\controllers;

use croacworks\essentials\models\Role;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * RoleController implements the CRUD actions for Role model.
 */
class RoleController extends AuthorizationController
{
    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->free = ['get-actions'];
    }

    /**
     * Lists all Role models.
     */
    public function actionIndex()
    {
        $searchModel = new Role(); // usando o próprio modelo para search
        $dataProvider = $searchModel->search($this->request->queryParams ?? []);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Lista todos os controllers disponíveis no sistema.
     */
    public static function getAllControllers(): array
    {
        $paths = [
            Yii::getAlias('@app/controllers'),
            Yii::getAlias('@app/controllers/rest'),
            Yii::getAlias('@vendor/weebz/yii2-basics/src/controllers'),
            Yii::getAlias('@vendor/weebz/yii2-basics/src/controllers/rest'),
        ];

        $controllers = [];

        foreach ($paths as $path) {
            if (!is_dir($path)) continue;

            $files = scandir($path);

            foreach ($files as $file) {
                if (!preg_match('/^(.*)Controller\.php$/', $file, $matches)) continue;

                $fullPath = $path . DIRECTORY_SEPARATOR . $file;
                if (!is_file($fullPath)) continue;

                $content = file_get_contents($fullPath);

                if (!preg_match('/namespace\s+([^;]+);/', $content, $nsMatch)) continue;
                if (!preg_match('/class\s+(\w+Controller)\b/', $content, $classMatch)) continue;

                $namespace = trim($nsMatch[1]);
                $className = trim($classMatch[1]);
                $fqcn = $namespace . '\\' . $className;

                $controllers[$fqcn] = $fqcn;
            }
        }

        return $controllers;
    }

    /**
     * AJAX: Retorna actions de um controller FQCN.
     */
    public function actionGetActions()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $controllerClass = Yii::$app->request->post('controller');

        if (!class_exists($controllerClass)) {
            return ['success' => false, 'message' => 'Controller não encontrado.'];
        }

        try {
            $methods = get_class_methods($controllerClass) ?: [];
            $actions = array_filter($methods, fn($method) => str_starts_with($method, 'action'));
            $actions = array_map(fn($a) => \yii\helpers\Inflector::camel2id(substr($a, 6)), $actions);

            return ['success' => true, 'actions' => array_values($actions)];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Displays a single Role model.
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Role model.
     */
    public function actionCreate()
    {
        $model = new Role();

        if ($this->request->isPost) {
            $post = $this->request->post();

            if ($model->load($post)) {
                $model->actions = isset($post['to']) ? implode(';', $post['to']) : null;
                $model->controller = trim($model->controller);
                $model->origin = isset($post['Role']['origin']) ? implode(';', $post['Role']['origin']) : '*';
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
     * Updates an existing Role model.
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $post = $this->request->post();
        $savedActions = $model->actions ? explode(';', $model->actions) : [];

        if ($this->request->isPost && $model->load($post)) {
            $model->actions = isset($post['to']) ? implode(';', $post['to']) : null;
            $model->controller = trim($model->controller);
            $model->origin = isset($post['Role']['origin']) ? implode(';', $post['Role']['origin']) : '*';

            if ($model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', [
            'model' => $model,
            'savedActions' => $savedActions,
        ]);
    }

    /**
     * Deletes an existing Role model.
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        return $this->redirect(['index']);
    }

    /**
     * Finds the Role model by ID.
     */
    protected function findModel($id, $model = null)
    {
        if (($model = Role::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
