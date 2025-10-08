<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = $model->isNewRecord ? Yii::t('app', 'Create Trigger') : Yii::t('app', 'Update Trigger');
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h5><?= Html::encode($this->title) ?></h5>

            <?php $form = ActiveForm::begin(); ?>

            <?= $form->field($model, 'name') ?>
            <?= $form->field($model, 'model_class')->textInput(['placeholder' => 'common\models\License']) ?>
            <?= $form->field($model, 'expression')->textarea(['rows' => 3, 'placeholder' => '$license->daysToExpire() <= 30']) ?>
            <?= $form->field($model, 'action_type')->dropDownList([
                'notify' => 'Notify',
                'call' => 'Call Model Method',
                'webhook' => 'Webhook',
                'command' => 'Command Line',
            ]) ?>
            <?= $form->field($model, 'action_target')->textInput(['placeholder' => 'notification_key, method, URL, etc.']) ?>
            <?= $form->field($model, 'cooldown_seconds')->textInput(['placeholder' => '0 (no limit)']) ?>
            <?= $form->field($model, 'enabled')->checkbox() ?>

            <div class="form-group mt-3">
                <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
