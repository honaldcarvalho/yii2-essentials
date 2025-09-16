<?php
namespace croacworks\essentials\controllers;

use Yii;

use yii\web\Response;
use croacworks\essentials\models\SystemInfo;

class DashboardController extends AuthorizationController
{
    public function actionHealth()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $sysInfo    = new SystemInfo();
        $diskInfo   = $sysInfo->diskInfo('/');
        $memoryInfo = $sysInfo->memoryInfo();
        $cpuInfo    = $sysInfo->cpuInfo();
        $osInfo     = $sysInfo->getOSInformation();

        return [
            'ok'        => true,
            'os'        => $osInfo,
            'disk'      => $diskInfo,
            'memory'    => $memoryInfo,
            'cpu'       => $cpuInfo,
            'timestamp' => time(),
        ];
    }
}
