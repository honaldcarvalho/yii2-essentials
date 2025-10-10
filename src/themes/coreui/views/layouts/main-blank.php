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

// ✅ Fancybox and jQuery must load before CoreUI
PluginAsset::register($this)->add(['fontawesome','icheck-bootstrap','fancybox','jquery-ui','toastr','select2','sweetalert2']);
CoreuiAsset::register($this);
FontAwesomeAsset::register($this);

$configuration = Configuration::get();
$assetDir = Yii::$app->assetManager->getPublishedUrl('@vendor/croacworks/yii2-essentials/src/themes/coreui/web');

// Redirect to login if user not logged
if (Yii::$app->user->identity === null) {
    return (new CommonController(0, 0))->redirect(['site/login']);
}

$theme = Yii::$app->user->identity->profile->theme;
$this->registerJs("yii.t = function(category, message){ return message; };", View::POS_END);
?>

<?php $this->beginPage() ?>
<!doctype html>
<html lang="<?= Yii::$app->language ?>" data-coreui-theme="dark">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
      <?= $this->title != '' ? Html::encode($configuration->title . ' - ' . $this->title) : Html::encode($configuration->title) ?>
    </title>

    <!-- 🧩 Prevent CoreUI color-modes crash on blank layout -->
    <script>
    // Neutralize CoreUI color-modes before it runs
    window.showActiveTheme = function(){
      try {
        const el = document.querySelector('[data-coreui-theme-value]');
        if (!el) {
          console.warn('CoreUI color-modes skipped (blank layout)');
          return;
        }
        // fallback if original exists
        if (window.CoreUI?.updateTheme) CoreUI.updateTheme();
      } catch(e){
        console.warn('CoreUI color-modes neutralized (blank layout)');
      }
    };
    </script>

    <?php $this->head(); ?>

    <?php
    // 🧠 Handle Fancybox + loading overlay safely
    $this->registerJs(<<<JS
    onPjaxReady((root) => {
      // Fancybox v4 detection
      if (typeof Fancybox !== 'undefined') {
        Fancybox.bind(root.find('[data-fancybox]').get());
      } else {
        console.warn('Fancybox not loaded (blank layout)');
      }

      // Custom overlay for iframe load
      $(document).off('click.fbx','[data-fancybox]').on('click.fbx','[data-fancybox]', function(){
        const overlay = '<div id="custom-loading" style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:9999;background:rgba(255,255,255,0.8);display:flex;align-items:center;justify-content:center;font-size:20px;">Loading...</div>';
        $('body').append(overlay);
      });

      // Hide overlay on Fancybox close/show
      $(document).on('afterShow.fb.pjax afterClose.fb.pjax', function(){
        $('#custom-loading').remove();
      });
    });
    JS, View::POS_END);
    ?>
  </head>

  <body>
    <?php $this->beginBody() ?>

    <?= $this->render('content_blank', [
        'content' => $content,
        'assetDir' => $assetDir,
        'theme' => $theme,
        'configuration' => $configuration
    ]) ?>

    <?php $this->endBody() ?>
  </body>
</html>
<?php $this->endPage() ?>
