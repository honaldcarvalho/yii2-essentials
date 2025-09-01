<?php

/** @var yii\web\View $this */
/** @var string $content */

use croacworks\essentials\controllers\CommonController;
use croacworks\essentials\models\Configuration;
use croacworks\essentials\themes\coreui\assets\CoreuiAsset;
use croacworks\essentials\themes\coreui\assets\FontAwesomeAsset;
use croacworks\essentials\themes\coreui\assets\PluginAsset;
use yii\helpers\Html;

CoreuiAsset::register($this);
FontAwesomeAsset::register($this);
PluginAsset::register($this)->add(['fontawesome', 'fancybox', 'sweetalert2']);
$configuration = Configuration::get();
$assetDir = Yii::$app->assetManager->getPublishedUrl('@vendor/croacworks/yii2-essentials/src/themes/coreui/web');
// if(Yii::$app->user->identity === null){
//     return (new CommonController(0,0))->redirect(['site/login']); 
// }
$theme = 'dark'; //Yii::$app->user->identity->theme;

?>
<?php $this->beginPage() ?>
<!doctype html>
<html lang="<?= Yii::$app->language ?>" data-coreui-theme="dark">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title><?= $this->title != '' ? $configuration->title . ' - ' . Html::encode($this->title) : $configuration->title  ?></title>
  <?php
  $this->head();
  ?>
</head>

<body>
  <?php $this->beginBody() ?>
  <div class="bg-body-tertiary min-vh-100 d-flex flex-row align-items-center">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <?= Alert::widget(); ?>
        </div>
      </div>
    </div>
  </div>
  <?= $content; ?>

  <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>