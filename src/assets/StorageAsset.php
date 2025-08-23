<?php
namespace croacworks\essentials\assets;

use yii\web\AssetBundle;

class StorageAsset extends AssetBundle
{
    // ajuste a alias conforme sua estrutura; se usar "src/assets", esta funciona:
    public $sourcePath = '@croacworks/essentials/assets/src';

    public $css = ['storage.css'];
    public $js  = ['storage.js'];

    public $depends = [
        'yii\web\JqueryAsset',
        'yii\bootstrap\BootstrapAsset',
        'yii\bootstrap\BootstrapPluginAsset',
    ];
}
