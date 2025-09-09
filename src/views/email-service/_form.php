<?php

use croacworks\essentials\widgets\AceEditor;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\EmailService */
/* @var $form yii\bootstrap5\ActiveForm */
?>

<div class="email-service-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'scheme')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'enable_encryption')->checkbox() ?>

    <?= $form->field($model, 'encryption')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'host')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'password')->passwordInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'port')->textInput() ?>

    <div class="form-group mb-3 mt-3">
        <?= Html::submitButton('<i class="fas fa-save mr-2"></i>'.Yii::t('app','Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
