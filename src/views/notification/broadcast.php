<?php
/** @var yii\web\View $this */
/** @var croacworks\essentials\models\forms\NotificationBroadcastForm $model */
/** @var array $userItems */
/** @var array $groupItems */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = Yii::t('app','Send notification');
$js = <<<JS
(function(){
  function onModeChange(){
    var val = document.querySelector('input[name="NotificationBroadcastForm[recipient_mode]"]:checked')?.value || 'user';
    var userDiv  = document.getElementById('field-user');
    var groupDiv = document.getElementById('field-group');
    if (val === 'user') { userDiv.style.display='block'; groupDiv.style.display='none'; }
    else if (val === 'group') { userDiv.style.display='none'; groupDiv.style.display='block'; }
    else { userDiv.style.display='none'; groupDiv.style.display='none'; }
  }
  function onPushToggle(){
    var chk = document.getElementById('notificationbroadcastform-push_expo');
    var box = document.getElementById('field-expo-data');
    box.style.display = (chk && chk.checked) ? 'block' : 'none';
  }

  document.querySelectorAll('input[name="NotificationBroadcastForm[recipient_mode]"]').forEach(function(r){
    r.addEventListener('change', onModeChange);
  });
  var push = document.getElementById('notificationbroadcastform-push_expo');
  if (push) push.addEventListener('change', onPushToggle);

  onModeChange();
  onPushToggle();
})();
JS;
$this->registerJs($js);

?>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><?= Html::encode($this->title) ?></h5>
  </div>
  <div class="card-body">
    <?php $form = ActiveForm::begin(['id' => 'broadcast-form', 'options' => ['autocomplete'=>'off']]); ?>

    <div class="row">
      <div class="col-md-4">
        <?= $form->field($model, 'recipient_mode')->radioList([
            'user'  => Yii::t('app','User'),
            'group' => Yii::t('app','Group'),
            'all'   => Yii::t('app','All (current scope)'),
        ], ['itemOptions' => ['class'=>'form-check-inline']]) ?>
      </div>

      <div class="col-md-4" id="field-user" style="display:none;">
        <?= $form->field($model, 'user_id')->dropDownList($userItems, ['prompt'=>Yii::t('app','Select a user')]) ?>
      </div>

      <div class="col-md-4" id="field-group" style="display:none;">
        <?= $form->field($model, 'group_id')->dropDownList($groupItems, ['prompt'=>Yii::t('app','Select a group')]) ?>
        <div class="form-check">
          <?= Html::activeCheckbox($model, 'include_children', ['label'=>Yii::t('app','Include subgroups')]) ?>
        </div>
      </div>
    </div>

    <div class="row mt-3">
      <div class="col-md-6">
        <?= $form->field($model, 'title')->textInput(['maxlength'=>255]) ?>
      </div>
      <div class="col-md-3">
        <?= $form->field($model, 'type')->textInput(['placeholder'=>'system']) ?>
      </div>
      <div class="col-md-3">
        <?= $form->field($model, 'url')->textInput(['placeholder'=>'/optional/path']) ?>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <?= $form->field($model, 'content')->textarea(['rows'=>4]) ?>
      </div>
    </div>

    <hr>

    <div class="row">
      <div class="col-md-3">
        <div class="form-check">
          <?= Html::activeCheckbox($model, 'push_expo', ['label'=>Yii::t('app','Send push (Expo)')]) ?>
        </div>
      </div>
      <div class="col-md-9" id="field-expo-data" style="display:none;">
        <?= $form->field($model, 'expo_data_json')->textarea([
            'rows'=>3,
            'placeholder'=>'{"screen":"Announcement","slug":"service-hours"}'
        ])->hint(Yii::t('app','Optional JSON. It will be attached as `data` in the push.')) ?>
      </div>
    </div>

    <div class="mt-3">
      <?= Html::submitButton(Yii::t('app','Send'), ['class'=>'btn btn-primary']) ?>
      <?= Html::a(Yii::t('app','Back'), ['index'], ['class'=>'btn btn-secondary ms-2']) ?>
    </div>

    <?php ActiveForm::end(); ?>
  </div>
</div>