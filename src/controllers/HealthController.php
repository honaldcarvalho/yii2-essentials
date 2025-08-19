<?php
namespace croacworks\essentials\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * Simple health check controller.
 * Exposed at /essentials/health/index when the module is enabled.
 */
class HealthController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionIndex(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return [
            'status'   => 'ok',
            'appId'    => Yii::$app->id,
            'time'     => date('c'),
            'php'      => PHP_VERSION,
            'database' => $this->checkDb(),
        ];
    }

    private function checkDb(): string
    {
        try {
            Yii::$app->db->createCommand('SELECT 1')->execute();
            return 'ok';
        } catch (\Throwable $e) {
            return 'error: ' . $e->getMessage();
        }
    }
}
