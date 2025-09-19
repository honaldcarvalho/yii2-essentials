<?php
namespace croacworks\essentials\assets;

use yii\web\AssetBundle;

class NotificationAsset extends AssetBundle
{
    public $sourcePath = '@croacworks/essentials/assets/notification'; // pasta virtual
    public $js = [
        'notifications-modal.js',  // o script do modal (SweetAlert)
        'notifications-actions.js' // o script com ações (mark read, delete, etc.)
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\web\JqueryAsset',
        // Se você já tem SweetAlert2 no tema, remova a linha abaixo.
        // Caso contrário, inclua via CDN no layout.
        // 'croacworks\essentials\assets\CoreUiAsset' // se existir
    ];
}
