<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\FormField */
/* @var $form yii\bootstrap5\ActiveForm */
?>

<div class="form-field-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'id')->textInput() ?>

    <?= $form->field($model, 'dynamic_form_id')->dropDownList(yii\helpers\ArrayHelper::map(app\models\DynamicForm::find()->asArray()->all(), 'id', 'name')) ?>

    <?= $form->field($model, 'label')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'type')->textInput() ?>

    <?= $form->field($model, 'default')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'model_class')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'model_field')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'order')->textInput() ?>

    <?= $form->field($model, 'status')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
