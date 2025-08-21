<?php

/** @var yii\web\View $this */

use croacworks\essentials\controllers\CommonController;
use croacworks\essentials\models\custom\DashboarSearch;
use croacworks\essentials\models\Configuration;

$params = Configuration::get();

$assetsDir =  CommonController::getAssetsDir();

if (!empty($params->file_id) && $params->file !== null) {
    $url = Yii::getAlias('@web') . $params->file->urlThumb;
    $logo_image = "<img alt='{$params->title}' width='150px' class='brand-image img-circle elevation-3' src='{$url}' style='opacity: .8' />";
} else {
    $logo_image = "<img src='{$assetsDir}/img/croacworks-logo-hq.png' width='150px' alt='{$params->title}' class='brand-image elevation-3' style='opacity: .8'>";
}
$this->title = '';

?>

<div class="site-index">

    <div class="jumbotron text-center bg-transparent">
        <p><?= $logo_image; ?></p>
        <h4 class="display-5"><?= $params->title ?></h4>

        <p class="lead"><?= $params->slogan ?></p>
    </div>

    <div class="body-content">
    </div>

</div>