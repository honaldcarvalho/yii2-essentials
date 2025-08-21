<?php
namespace croacworks\essentials\themes\coreui\assets;

use yii\web\AssetBundle;

class CoreuiAsset extends AssetBundle
{

    public $sourcePath = '@vendor/croacworks/yii2-essentials/src/themes/coreui/web';

    public $css = [
        'css/coreui.min.css',
        'css/bootstrap.min.css',
    ];
    
    public $js = [
        'js/coreui.bundle.min.js',
        'js/main.js',
        'js/config.js',
        'js/color-modes.js',
        'js/utils.js',
        'js/t.js',
    ];

    public $depends = [
        'croacworks\essentials\themes\coreui\assets\BaseAsset',
        'croacworks\essentials\themes\coreui\assets\PluginAsset',
        '\yii\web\JqueryAsset'
    ];
}