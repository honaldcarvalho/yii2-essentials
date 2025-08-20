<?php

namespace croacworks\essentials\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * Simple health check controller.
 * Exposed at /essentials/health/index when the module is enabled.
 */
class ControllerCommon extends Controller
{
    public $enableCsrfValidation = false;
    public function behaviors()
    {
        $b = parent::behaviors();
        $b['accessByRole'] = [
            'class' => AccessByRoleFilter::class,
            'public' => ['login', 'error'], // rotas públicas deste controller
        ];
        return $b;
    }
}
