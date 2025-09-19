<?php
namespace croacworks\essentials\themes\coreui\assets;

use yii\web\AssetBundle;

class NotificationAsset extends AssetBundle
{
    // aponto para a pasta onde realmente estão os arquivos
    public $sourcePath = '@vendor/croacworks/yii2-essentials/src/themes/coreui/web/notifications';

    public $js = [
        'notifications-actions.js',
        'notifications-modal.js',
    ];

    public $depends = [
        'yii\web\YiiAsset',
        'yii\web\JqueryAsset',
    ];
}
