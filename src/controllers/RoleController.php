<?php

namespace croacworks\essentials\controllers;

use Yii;

use yii\db\Query;
use ReflectionClass;
use ReflectionMethod;
use yii\helpers\Inflector;
use yii\web\NotFoundHttpException;
use croacworks\essentials\models\Group;
use croacworks\essentials\models\Role;
use yii\filters\VerbFilter;

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

    public function behaviors()
    {
        $b = parent::behaviors();
        $b['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'apply-templates'     => ['POST'],
                'apply-templates-all' => ['POST'],
                'remove-roles'        => ['POST'],
                'get-actions'         => ['POST'],
            ],
        ];
        return $b;
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
            Yii::getAlias('@vendor/croacworks/yii2-essentials/src/controllers'),
            Yii::getAlias('@vendor/croacworks/yii2-essentials/src/controllers/rest'),
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

    private static function collectControllerActions(string $controllerClass, bool $withOrigins = false): array
    {
        $byMethod = [];
        $origins  = [];

        // 1) Métodos action* (públicos), incluindo herdados
        $ref = new ReflectionClass($controllerClass);
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();
            if (str_starts_with($name, 'action') && $name !== 'actions') {
                // transforma actionXpto => xpto (camel2id)
                $id = Inflector::camel2id(substr($name, 6));
                $byMethod[$id] = true;
                if ($withOrigins) {
                    $origins[$id] = $method->getDeclaringClass()->getName();
                }
            }
        }

        // 2) External actions via actions() (se possível instanciar o controller)
        $byMap = [];
        try {
            // Deriva um ID simples a partir do nome da classe (EmailServiceController => email-service)
            $short = preg_replace('/Controller$/', '', $ref->getShortName());
            $id    = Inflector::camel2id($short);

            // Tenta instanciar: __construct($id, $module, $config = [])
            /** @var \yii\web\Controller $instance */
            $instance = new $controllerClass($id, \Yii::$app);
            $map = $instance->actions();
            if (is_array($map)) {
                foreach (array_keys($map) as $key) {
                    $key = trim((string)$key);
                    if ($key !== '') {
                        $byMap[$key] = true;
                        if ($withOrigins && !isset($origins[$key])) {
                            // marca origem como "actions()"
                            $origins[$key] = $controllerClass . '::actions()';
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // silencioso: se não der p/ instanciar, apenas não coleta actions() mapeadas
        }

        // 3) Merge final (set union)
        $all = array_keys($byMethod + $byMap);
        sort($all);

        // Se quiser devolver as origens também:
        if ($withOrigins) {
            // mantém apenas origens de keys existentes
            $orig = [];
            foreach ($all as $k) {
                $orig[$k] = $origins[$k] ?? $controllerClass;
            }
            return ['list' => $all, 'origins' => $orig];
        }

        return $all;
    }

    /**
     * AJAX: Retorna actions de um controller FQCN (inclui herdadas e as de actions()).
     */
    public function actionGetActions()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $controllerClass = \Yii::$app->request->post('controller');

        if (!is_string($controllerClass) || !class_exists($controllerClass)) {
            return ['success' => false, 'message' => 'Controller não encontrado.'];
        }

        try {
            // Se quiser ver de onde veio cada action, troque para true
            $result = self::collectControllerActions($controllerClass, false);

            return [
                'success' => true,
                'actions' => array_values($result),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Aplica (ou reaplica) templates de roles conforme o nível do grupo.
     * - Apaga somente roles originadas por template (origin='*') quando $reseed=true
     * - Insere a partir de roles_templates.level == $group->level
     *
     * POST /role/apply-templates?group_id=123&reseed=1
     */
    public function actionApplyTemplates(int $group_id, int $reseed = 1)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $group = Group::findOne($group_id);
        if (!$group) {
            throw new NotFoundHttpException('Grupo não encontrado.');
        }

        $level = (string)$group->level; // 'master' | 'admin' | 'user'
        if ($level === '') {
            return ['success' => false, 'message' => 'Grupo sem nível definido.'];
        }

        $db = Yii::$app->db;
        $tx = $db->beginTransaction();
        try {
            if ($reseed) {
                // Remove apenas as auto-geradas (origin='*')
                $db->createCommand()->delete('{{%roles}}', [
                    'group_id' => $group->id,
                    'origin'   => '*',
                ])->execute();
            }

            // Insere via INSERT ... SELECT
            $sql = "
                INSERT INTO {{%roles}} 
                    (`name`, `user_id`, `group_id`, `controller`, `actions`, `origin`, `created_at`, `updated_at`, `status`)
                SELECT
                    NULL, NULL, :gid, rt.controller, rt.actions, rt.origin, NOW(), NOW(), rt.status
                FROM {{%roles_templates}} rt
                WHERE rt.level = :level
            ";

            $inserted = $db->createCommand($sql, [
                ':gid'   => $group->id,
                ':level' => $level,
            ])->execute();

            $tx->commit();
            return [
                'success'  => true,
                'group_id' => (int)$group->id,
                'level'    => $level,
                'inserted' => (int)$inserted,
                'message'  => "Templates aplicados com sucesso para o grupo {$group->id} ({$level})."
            ];
        } catch (\Throwable $e) {
            $tx->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * (Opcional) Aplica templates para TODOS os grupos de um certo nível,
     * ou para todos os níveis se $level estiver vazio.
     *
     * POST /role/apply-templates-all?level=admin&reseed=1
     */
    public function actionApplyTemplatesAll(string $level = '', int $reseed = 1)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $query = Group::find();
        if ($level !== '') {
            $query->andWhere(['level' => $level]);
        }

        $groups = $query->all();
        $results = [];

        foreach ($groups as $g) {
            // Reaproveita o método acima via chamada interna
            Yii::$app->request->setBodyParams([]); // garante POST body vazio
            $res = $this->actionApplyTemplates((int)$g->id, $reseed);
            $results[] = $res;
        }

        return ['success' => true, 'count' => count($results), 'results' => $results];
    }

    /**
     * Remove roles de um grupo.
     *
     * POST /role/remove-roles?group_id=2&only_auto=0&dry_run=0
     * - only_auto=1 apaga apenas origin='*'
     * - dry_run=1 só conta, não apaga
     */
    public function actionRemoveRoles(int $group_id, int $only_auto = 0, int $dry_run = 0)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // (opcional) validar existência do grupo:
        // if (!Group::find()->where(['id' => $group_id])->exists()) {
        //     throw new NotFoundHttpException('Grupo não encontrado.');
        // }

        $where = ['group_id' => $group_id];
        if ($only_auto) {
            $where['origin'] = '*';
        }

        $db = Yii::$app->db;

        // Conta quantos registros se enquadram no filtro
        $total = (new Query())->from('{{%roles}}')->where($where)->count('*', $db);

        if ($dry_run) {
            return [
                'success'     => true,
                'dry_run'     => true,
                'group_id'    => (int)$group_id,
                'only_auto'   => (bool)$only_auto,
                'to_delete'   => (int)$total,
                'deleted'     => 0,
                'message'     => 'Dry-run: nenhuma role foi removida.',
            ];
        }

        $tx = $db->beginTransaction();
        try {
            $deleted = $db->createCommand()->delete('{{%roles}}', $where)->execute();
            $tx->commit();

            return [
                'success'   => true,
                'group_id'  => (int)$group_id,
                'only_auto' => (bool)$only_auto,
                'requested' => (int)$total,
                'deleted'   => (int)$deleted,
                'message'   => "Remoção concluída para o grupo {$group_id}.",
            ];
        } catch (\Throwable $e) {
            $tx->rollBack();
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
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
