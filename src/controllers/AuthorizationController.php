<?php

namespace croacworks\essentials\controllers;

use Yii;

use croacworks\essentials\models\License;
use croacworks\essentials\models\Log;
use croacworks\essentials\models\Role;
use croacworks\essentials\models\User;
use croacworks\essentials\models\UserGroup;

use yii\behaviors\TimestampBehavior;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;

class AuthorizationController extends CommonController
{
    const ADMIN_GROUP_ID = 2;
    public $free = ['login', 'signup', 'error'];

    public static function isGuest()
    {
        return Yii::$app->user->isGuest;
    }

    public static function User(): ?User
    {
        return Yii::$app->user->identity;
    }
    
    static function userGroup()
    {
        $user_groups = [];

        $authHeader = Yii::$app->request->getHeaders()->get('Authorization');
        if (!$authHeader || !preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            if (!self::isGuest())
                return Yii::$app->session->get('group')->id;
        }

        $user = self::getUserByToken();
        if ($user)
            $user_groups = $user->getUserGroupsId();

        return end($user_groups);
    }

    public static function getUserGroups()
    {
        return self::isGuest()
            ? self::getUserByToken()?->getUserGroupsId()
            : self::User()?->getUserGroupsId();
    }

    public static function isAdmin(): bool
    {
        return !self::isGuest() && UserGroup::find()
            ->where(['user_id' => Yii::$app->user->id, 'group_id' => self::ADMIN_GROUP_ID])
            ->exists();
    }

    public static function getUserByToken()
    {
        $authHeader = Yii::$app->request->getHeaders()->get('Authorization');
        if ($authHeader && preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            return User::find()
                ->where(['status' => User::STATUS_ACTIVE])
                ->andWhere(['or', ['access_token' => $matches[1]], ['auth_key' => $matches[1]]])
                ->one();
        }
        return null;
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $request = Yii::$app->request;

        $controller = $this;
        $action = $this->action->id;

        $show = $this->pageAuth();
        if (in_array($action, $this->free) || self::isAdmin()) {
            $show = true;
        }

        $behaviors = [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'value' => fn() => date('Y-m-d H:i:s'),
            ],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => $this->free,
                        'roles' => ['?'],
                    ],
                    [
                        'allow' => $show,
                        'actions' => [$action],
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];

        if ($this->params->logging && $controller->id != 'log') {
            if (Yii::$app->user->identity !== null) {
                $log = new Log();
                $log->action = $action;
                $log->ip = $this->getUserIP();
                $log->device = $this->getOS();
                $log->controller = Yii::$app->controller->id;
                $log->user_id = Yii::$app->user->identity->id;

                if ($request->get('id')) {
                    $log->data = $request->get('id');
                } elseif ($request->post()) {
                    $data_json = json_encode($request->post());
                    if (!str_contains($data_json, 'password')) {
                        $log->data = $data_json;
                    }
                }

                $log->save();
            }
        }

        return $behaviors;
    }

    public function pageAuth()
    {
        $show = false;

        if (!self::isGuest()) {
            $controllerFQCN = static::getClassPath(); // <- Corrigido aqui
            $request_action = Yii::$app->controller->action->id;
            $groups = self::User()->getUserGroupsId();

            $query = Role::find()
                ->where([
                    'controller' => $controllerFQCN,
                    'status' => 1,
                ])
                ->andWhere(['or', ['in', 'group_id', $groups], ['group_id' => self::User()->group_id]])
                ->all();

            foreach ($query as $role) {
                $actions = explode(';', $role->actions ?? '');
                foreach ($actions as $action) {
                    if (trim($action) === trim($request_action)) {
                        $show = true;
                        break 2;
                    }
                }
            }
        }
        return $show;
    }

    public static function verAuthorization($controllerFQCN, $request_action, $model = null)
    {
        if (self::isGuest()) return false;
        if (self::isAdmin()) return true;

        if (self::verifyLicense() === null) {
            Yii::$app->session->setFlash('warning', Yii::t('app', 'License expired!'));
            return false;
        }

        $groups = self::User()->getUserGroupsId();

        if ($model && $model->verGroup) {
            if ($request_action === 'view' && $model->group_id == 1) {
                return true;
            }
            if (!in_array($model->group_id, $groups)) {
                return false;
            }
        }

        $roles = Role::find()
            ->where(['controller' => $controllerFQCN, 'status' => 1])
            ->andWhere(['in', 'group_id', $groups])
            ->all();

        foreach ($roles as $role) {
            $actions = explode(';', $role->actions);
            if (in_array($request_action, $actions)) {
                return true;
            }
        }

        return false;
    }

    public static function verifyLicense()
    {
        $groups = self::User()?->getUserGroupsId();
        if (self::isAdmin()) return true;

        $licenses = License::find()->where(['in', 'group_id', $groups])->all();

        foreach ($licenses as $license) {
            if (strtotime($license->validate) >= strtotime(date('Y-m-d')) && $license->status) {
                return $license;
            }
        }

        return null;
    }

    protected function findModel($id, $model_name = null)
    {
        $modelClass = $model_name ?? str_replace(['controllers', 'Controller'], ['models', ''], static::getClassPath());
        $model = $modelClass::find()->where(['id' => $id]);
        $modelObj = new $modelClass;

        if (
            property_exists($modelObj, 'verGroup') &&
            $modelObj->verGroup &&
            !self::isAdmin()
        ) {
            $groups = self::User()->getUserGroupsId();

            if (Yii::$app->controller->action->id === 'view') {
                $groups[] = 1;
            }

            $model->andFilterWhere(['in', 'group_id', $groups]);
        }

        if (($instance = $model->one()) !== null) {
            return $instance;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}