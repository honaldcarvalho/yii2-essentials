<?php
namespace croacworks\essentials\themes\coreui\assets;

use yii\web\AssetBundle;

class NotificationAsset extends AssetBundle
{
    // aponto para a pasta onde realmente estão os arquivos
    public $sourcePath = '@croacworks/essentials/web/notifications';

    public $js = [
        'notifications-actions.js',
        'notifications-modal.js',
    ];

    public $depends = [
        'yii\web\YiiAsset',
        'yii\web\JqueryAsset',
    ];
}
