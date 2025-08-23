<?php

/** @var yii\web\View $this */

use croacworks\essentials\controllers\CommonController;
use croacworks\essentials\models\custom\DashboarSearch;
use croacworks\essentials\models\Configuration;

$config = Configuration::get();

$assetsDir =  CommonController::getAssetsDir();

if (!empty($config->file_id) && $config->file !== null) {
    $url = Yii::getAlias('@web') . $config->file->urlThumb;
    $logo_image = "<img alt='{$config->title}' width='150px' class='brand-image img-circle elevation-3' src='{$url}' style='opacity: .8' />";
} else {
    $logo_image = "<img src='{$assetsDir}/images/croacworks-logo-hq.png' width='150px' alt='{$config->title}' class='brand-image elevation-3' style='opacity: .8'>";
}
$this->title = '';

?>

<div class="site-index">

    <div class="jumbotron text-center bg-transparent">
        <p><?= $logo_image; ?></p>
        <h4 class="display-5"><?= $config->title ?></h4>

        <p class="lead"><?= $config->slogan ?></p>
    </div>

    <div class="body-content">
    </div>

</div>