<?php

/** @var yii\web\View $this */
/** @var string $content */

use croacworks\essentials\assets\PjaxHelperAsset;
use croacworks\essentials\controllers\CommonController;
use croacworks\essentials\models\Configuration;
use croacworks\essentials\themes\coreui\assets\CoreuiAsset;
use croacworks\essentials\themes\coreui\assets\FontAwesomeAsset;
use croacworks\essentials\themes\coreui\assets\PluginAsset;
use yii\helpers\Html;
use yii\web\View;

PjaxHelperAsset::register($this);
CoreuiAsset::register($this);
FontAwesomeAsset::register($this);
PluginAsset::register($this)->add(['fontawesome', 'icheck-bootstrap', 'fancybox', 'jquery-ui', 'toastr', 'select2', 'sweetalert2']);
$configuration = Configuration::get();
$assetDir = Yii::$app->assetManager->getPublishedUrl('@vendor/croacworks/yii2-essentials/src/themes/coreui/web');
if (Yii::$app->user->identity === null) {
  return (new CommonController(0, 0))->redirect(['site/login']);
}
$theme = Yii::$app->user->identity->profile->theme;

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
  $this->registerJs(<<<'JS'
  (function($){
    function showOverlay(){
      if (!$('#custom-loading').length) {
        $('body').append(
          '<div id="custom-loading" style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:9999;background:rgba(255,255,255,0.8);display:flex;align-items:center;justify-content:center;font-size:20px;">Carregando...</div>'
        );
      }
    }
    function hideOverlay(){ $('#custom-loading').remove(); }

    function bindFancybox(scope){
      var $root = scope ? $(scope) : $(document);

      // Rebind seguro
      if (window.Fancybox && typeof Fancybox.bind === 'function') {
        Fancybox.bind($root.find('[data-fancybox]').get(), {
          on: {
            done: () => hideOverlay(),
            reveal: () => hideOverlay(),
            destroy: () => hideOverlay()
          }
        });
      }

      // Overlay enquanto abre (ao clicar)
      $(document).off('click.fbx','[data-fancybox]').on('click.fbx','[data-fancybox]', function(){
        showOverlay();
        // fallback: se nada abrir, some depois de 6s
        setTimeout(hideOverlay, 6000);
      });
    }

    // Primeiro bind
    bindFancybox(document);

    // Rebind após PJAX
    $(document).on('pjax:end', function(e){
      bindFancybox(e.target);
    });
  })(jQuery);
  JS, View::POS_END);
  ?>
</head>

<body>
  <?php $this->beginBody() ?>

  <?= $this->render('sidebar', ['assetDir' => $assetDir, 'theme' => $theme, 'configuration' => $configuration]) ?>
  <?= $this->render('content', ['content' => $content, 'assetDir' => $assetDir, 'theme' => $theme, 'configuration' => $configuration]) ?>

  <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>