<?php
namespace croacworks\essentials\behaviors;

use Yii;
use yii\base\ActionFilter;
use yii\web\ForbiddenHttpException;

/**
 * Intercepta beforeAction e consulta o componente `authorization`.
 * Configure em 'as authorization' no config do app.
 */
class AuthorizationBehavior extends ActionFilter
{
    /** @var string[] Rotas públicas (id no formato "controller/action") */
    public $publicRoutes = ['site/login', 'site/error'];

    /** @var string[] Rotas ignoradas (sempre liberadas) - aceita curingas "debug/*" */
    public $except = [];

    protected function isPublic(string $route): bool
    {
        if (in_array($route, $this->publicRoutes, true)) {
            return true;
        }
        foreach ($this->except as $pattern) {
            if (substr($pattern, -2) === '/*') {
                $prefix = substr($pattern, 0, -2);
                if (strpos($route, $prefix . '/') === 0) return true;
            } elseif ($pattern === $route) {
                return true;
            }
        }
        return false;
    }

    public function beforeAction($action)
    {
        $route = strtolower($action->controller->id . '/' . $action->id);

        if ($this->isPublic($route)) {
            return parent::beforeAction($action);
        }

        if (Yii::$app->user->isGuest) {
            Yii::$app->user->loginRequired();
            return false;
        }

        $controllerFqcn = get_class($action->controller);
        $actionId       = $action->id;

        $authz = Yii::$app->get('authorization');
        if (!$authz || !$authz->can($controllerFqcn, $actionId, Yii::$app->user->id)) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You are not allowed to perform this action.')
            );
        }

        return parent::beforeAction($action);
    }
}
