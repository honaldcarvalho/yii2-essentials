<?php
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use croacworks\essentials\models\User;
use croacworks\essentials\models\Language;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\User $model */

$languages = \yii\helpers\ArrayHelper::map(
    Language::find()->select(['id','name'])->asArray()->all(),
    'id','name'
);
?>

<div class="">
  <?php $form = ActiveForm::begin([
      'id' => 'user-search-form',
      'action' => ['index'],
      'method' => 'get',
      'options' => ['data-pjax' => 1],
  ]); ?>

  <div class="row g-3">
    <div class="col-md-2">
      <label class="form-label">ID</label>
      <?= Html::textInput('User[id:number]', $_GET['User']['id'] ?? '', [
        'class' => 'form-control',
        'inputmode' => 'numeric',
        'pattern' => '\d*',
      ]) ?>
    </div>

    <div class="col-md-3">
      <label class="form-label"><?= Yii::t('app','Username') ?></label>
      <?= Html::textInput('User[username:string]', $_GET['User']['username'] ?? '', [
        'class' => 'form-control',
        'maxlength' => true,
      ]) ?>
    </div>

    <div class="col-md-3">
      <label class="form-label">Email</label>
      <?= Html::textInput('User[email:string]', $_GET['User']['email'] ?? '', [
        'class' => 'form-control',
        'maxlength' => true,
      ]) ?>
    </div>

    <div class="col-md-4">
      <label class="form-label"><?= Yii::t('app','Full name') ?></label>
      <?= Html::textInput('User[profile.fullname:string]', $_GET['User']['profile.fullname'] ?? '', [
        'class' => 'form-control mirror-to',
        'data-alias' => 'User[profile__fullname:string]',
        'maxlength' => true,
        'placeholder' => Yii::t('app','Full name'),
      ]) ?>
      <!-- espelho para convenção com "__" -->
      <?= Html::hiddenInput('User[profile__fullname:string]', $_GET['User']['profile__fullname'] ?? '') ?>
    </div>

    <div class="col-md-2">
      <label class="form-label"><?= Yii::t('app','Status') ?></label>
      <?= Html::dropDownList('User[status:number]', $_GET['User']['status'] ?? '', [
          User::STATUS_ACTIVE   => Yii::t('app','Active'),
          User::STATUS_INACTIVE => Yii::t('app','Inactive'),
          User::STATUS_DELETED  => Yii::t('app','Deleted'),
      ], ['class' => 'form-select','prompt' => Yii::t('app','All')]) ?>
    </div>

    <?php if ($model->hasAttribute('language_id')): ?>
    <div class="col-md-2">
      <label class="form-label"><?= Yii::t('app','Language') ?></label>
      <?= Html::dropDownList('User[language_id:number]', $_GET['User']['language_id'] ?? '', $languages, [
          'class' => 'form-select select2',
          'prompt' => Yii::t('app','All'),
      ]) ?>
    </div>
    <?php endif; ?>

    <div class="col-md-3">
      <label class="form-label"><?= Yii::t('app','Created from') ?></label>
      <?= Html::input('date', 'User[created_atFDTsod:string]', $_GET['User']['created_atFDTsod'] ?? '', [
        'class' => 'form-control',
      ]) ?>
    </div>

    <div class="col-md-3">
      <label class="form-label"><?= Yii::t('app','Created until') ?></label>
      <?= Html::input('date', 'User[created_atFDTeod:string]', $_GET['User']['created_atFDTeod'] ?? '', [
        'class' => 'form-control',
      ]) ?>
    </div>

    <div class="col-md-3">
      <label class="form-label"><?= Yii::t('app','Updated from') ?></label>
      <?= Html::input('date', 'User[updated_atFDTsod:string]', $_GET['User']['updated_atFDTsod'] ?? '', [
        'class' => 'form-control',
      ]) ?>
    </div>

    <div class="col-md-3">
      <label class="form-label"><?= Yii::t('app','Updated until') ?></label>
      <?= Html::input('date', 'User[updated_atFDTeod:string]', $_GET['User']['updated_atFDTeod'] ?? '', [
        'class' => 'form-control',
      ]) ?>
    </div>
  </div>

  <div class="mt-3 d-flex gap-2">
    <?= Html::submitButton(Yii::t('app','Search'), ['class' => 'btn btn-primary']) ?>
    <button type="button" class="btn btn-secondary btn-reset" data-form="#user-search-form">
      <?= Yii::t('app','Reset') ?>
    </button>
  </div>

  <?php ActiveForm::end(); ?>
</div>

<?php
$js = <<<JS
(function(){
  // espelha o valor do input com ponto -> campo oculto com "__"
  function mirror(el){
    var aliasName = el.getAttribute('data-alias');
    if (!aliasName) return;
    var hidden = document.querySelector('input[name="'+aliasName+'"]');
    if (!hidden) return;
    hidden.value = el.value === '' ? '' : el.value;
  }
  var mirrors = document.querySelectorAll('#user-search-form .mirror-to');
  mirrors.forEach(function(el){
    el.addEventListener('input', function(){ mirror(el); });
    el.addEventListener('change', function(){ mirror(el); });
    mirror(el); // estado inicial
  });

  if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
    jQuery('#user-search-form .select2').select2({ width: '100%' });
  }
})();
JS;
$this->registerJs($js, \yii\web\View::POS_END);
