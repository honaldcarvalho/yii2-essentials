<?php
namespace croacworks\essentials\themes\coreui\assets;

use yii\web\AssetBundle;

class CoreuiAsset extends AssetBundle
{

    public $sourcePath = '@vendor/croacworks/yii2-essentials/src/themes/coreui/web';

    public $css = [
        'vendors/simplebar/css/simplebar.css',
        'css/vendors/simplebar.css',
        //'vendors/@coreui/chartjs/css/coreui-chartjs.css',
        'css/style.css',
        'css/custom.css',
        'css/forms.css',
    ];
    
    public $js = [
        'vendors/simplebar/js/simplebar.min.js',
        // 'vendors/chart.js/js/chart.umd.js',
        // 'vendors/@coreui/chartjs/js/coreui-chartjs.js',
        'vendors/@coreui/coreui/js/coreui.bundle.min.js',
        'vendors/@coreui/utils/js/index.js',
        'js/config.js',
        'js/color-modes.js',
        //'js/charts.js',
        'js/popovers.js',
        'js/colors.js',
        //'js/widgets.js',
        'js/tooltips.js',
        //'js/main.js',
    ];

    public $depends = [
        'croacworks\essentials\themes\coreui\assets\BaseAsset',
        'croacworks\essentials\themes\coreui\assets\PluginAsset',
        //'\yii\web\JqueryAsset'
    ];
}