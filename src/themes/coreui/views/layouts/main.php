<?php

/** @var yii\web\View $this */
/** @var string $content */

use croacworks\essentials\controllers\ControllerCommon;
use croacworks\essentials\models\Configuration;
use croacworks\essentials\themes\coreui\assets\CoreuiAsset;
use croacworks\essentials\themes\coreui\assets\FontAwesomeAsset;
use croacworks\essentials\themes\coreui\assets\PluginAsset;
use yii\helpers\Html;

CoreuiAsset::register($this);
FontAwesomeAsset::register($this);
PluginAsset::register($this)->add(['jquery','fontawesome', 'icheck-bootstrap','fancybox','jquery-ui','toastr','select2','sweetalert2']);
$configuration = Configuration::get();
$assetDir = Yii::$app->assetManager->getPublishedUrl('@vendor/croacworks/yii2-essentials/src/themes/coreui/web');
// if(Yii::$app->user->identity === null){
//     return (new ControllerCommon(0,0))->redirect(['site/login']); 
// }
$theme = 'dark';//Yii::$app->user->identity->theme;

?>
<?php $this->beginPage() ?>
<!doctype html >
<html lang="<?= Yii::$app->language ?>" data-coreui-theme="dark">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= $this->title != '' ? $configuration->title . ' - ' . Html::encode($this->title) : $configuration->title  ?></title>
    <?php 
    $this->head(); 
    $script = <<< JS
        Fancybox.bind("[data-fancybox]");
        $(document).on('click', '[data-fancybox]', function () {
            if($.fancybox === undefined || $.fancybox === null) {
                console.log('Fancybox is not defined. Please ensure the Fancybox plugin is loaded.');
            } else {
                $.fancybox.showLoading = function () {
                    if ($('#custom-loading').length === 0) {
                        $('body').append('<div id="custom-loading" style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:9999;background:rgba(255,255,255,0.8);display:flex;align-items:center;justify-content:center;font-size:20px;">Carregando...</div>');
                    }
                };

                $.fancybox.hideLoading = function () {
                    $('#custom-loading').remove();
                };

                $.fancybox.showLoading();
            }
        });

        // Esconde após abrir o fancybox
        $(document).on('afterShow.fb', function () {
            $.fancybox.hideLoading();
        });

        // Também remove ao fechar (garantia extra)
        $(document).on('afterClose.fb', function () {
            $.fancybox.hideLoading();
        });
    JS;
    $this->registerJs($script);
    ?>
  </head>
  <body>
    <?php $this->beginBody() ?>

    <?= $this->render('sidebar', ['assetDir' => $assetDir,'theme'=>$theme,'configuration'=>$configuration]) ?>
    <?= $this->render('content', ['content' => $content, 'assetDir' => $assetDir,'theme'=>$theme,'configuration'=>$configuration]) ?>

    <?php $this->endBody() ?>
  </body>
</html>
<?php $this->endPage() ?>