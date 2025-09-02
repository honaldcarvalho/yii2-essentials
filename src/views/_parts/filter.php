<?php
$path = explode('\\', get_class($searchModel));
$modelName = end($path);

// detecta se deve iniciar aberto
$isOpen = false;
if (isset($_GET[$modelName])) {
    foreach ((array)$_GET[$modelName] as $valor) {
        if ($valor !== '' && $valor !== null) {
            $isOpen = true;
            break;
        }
    }
}

$js = <<<JS
(function initFilterCollapse(){
  function setIcon(open){
    var icon = document.getElementById('collapseToggleIcon');
    if (!icon) return;
    icon.classList.toggle('fa-plus', !open);
    icon.classList.toggle('fa-minus', open);
  }

  var collapseEl = document.getElementById('collapseExample');
  if (!collapseEl) return;

  // estado inicial (caso já venha "show")
  setIcon(collapseEl.classList.contains('show'));

  // ouvintes CoreUI 5
  collapseEl.addEventListener('show.coreui.collapse', function(){ setIcon(true); });
  collapseEl.addEventListener('shown.coreui.collapse', function(){ setIcon(true); });
  collapseEl.addEventListener('hide.coreui.collapse', function(){ setIcon(false); });
  collapseEl.addEventListener('hidden.coreui.collapse', function(){ setIcon(false); });

  // fallback Bootstrap (se aplicável)
  collapseEl.addEventListener('show.bs.collapse', function(){ setIcon(true); });
  collapseEl.addEventListener('shown.bs.collapse', function(){ setIcon(true); });
  collapseEl.addEventListener('hide.bs.collapse', function(){ setIcon(false); });
  collapseEl.addEventListener('hidden.bs.collapse', function(){ setIcon(false); });
})();
JS;

$this->registerJs($js, \yii\web\View::POS_END);
?>

<div class="card">
  <div class="card-header">
    <button
      type="button"
      class="btn btn-tool w-100"
      data-coreui-toggle="collapse"
      data-coreui-target="#collapseExample"
      aria-controls="collapseExample"
      aria-expanded="<?= $isOpen ? 'true' : 'false' ?>"
    >
      <span class="float-start">
        <i class="fa fa-filter"></i> <?= Yii::t('app', 'Filters') ?>
      </span>
      <i id="collapseToggleIcon"
         class="fas <?= $isOpen ? 'fa-minus' : 'fa-plus' ?> float-end"></i>
    </button>
  </div>

  <div class="collapse <?= $isOpen ? 'show' : '' ?>" id="collapseExample">
    <div class="card card-body">
      <?= $this->render("{$view}/_search", ['model' => $searchModel]) ?>
    </div>
  </div>
</div>
