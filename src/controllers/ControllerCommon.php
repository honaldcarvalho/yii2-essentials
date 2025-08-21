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
}
