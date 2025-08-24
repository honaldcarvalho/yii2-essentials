<?php
namespace croacworks\essentials\themes\coreui\assets;

use yii\web\AssetBundle;

class CoreuiAsset extends AssetBundle
{

    public $sourcePath = '@vendor/croacworks/yii2-essentials/src/themes/coreui/web';

    public $css = [
        'vendors/simplebar/css/simplebar.css',
        'css/vendors/simplebar.css',
        'css/style.css',
        'css/custom.css',
    ];
    
    public $js = [
        'js/config.js',
        'js/color-modes.js',
        'vendors/@coreui/coreui/js/coreui.bundle.min.js',
        'vendors/simplebar/js/simplebar.min.js',
        'vendors/@coreui/utils/js/index.js',
        'js/popovers.js',
        'js/colors.js',
        'js/widgets.js',
        'js/tooltips.js',
        'js/charts.js',
        'js/main.js',
    ];

    public $depends = [
        'croacworks\essentials\themes\coreui\assets\BaseAsset',
        'croacworks\essentials\themes\coreui\assets\PluginAsset',
        '\yii\web\JqueryAsset'
    ];
}