<?php
namespace croacworks\essentials\assets;

use yii\web\AssetBundle;
/**
 USAGE:

$this->registerJs(<<<JS
onPjaxReady((root) => {

});
JS);
 */
class PjaxHelperAsset extends AssetBundle
{
    public $sourcePath = '@vendor/croacworks/yii2-essentials/src/assets/pjax-helper';
    public $js = ['pjax-bootstrap.js'];
    public $depends = [
        \yii\web\YiiAsset::class,       // garante jQuery + yii.js
        \yii\widgets\PjaxAsset::class,  // garante pjax.js
        \yii\bootstrap5\BootstrapPluginAsset::class,
    ];
}
