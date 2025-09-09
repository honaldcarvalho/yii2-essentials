<?php

use croacworks\essentials\models\EmailService;
use croacworks\essentials\models\Group;
use croacworks\essentials\models\Language;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\ParamSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="row mt-2">
    <div class="col-md-12">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>
    <?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'group_id')->dropdownList(yii\helpers\ArrayHelper::map(Group::find()->asArray()->all(), 'id', 'name'),['prompt'=>'']) ?>
    <?= $form->field($model, 'language_id')->dropdownList(yii\helpers\ArrayHelper::map(Language::find()->asArray()->all(), 'id', 'name'),['prompt'=>'']) ?>
    <?= $form->field($model, 'email_service_id')->dropdownList(yii\helpers\ArrayHelper::map(EmailService::find()->asArray()->all(), 'id', 'description'),['prompt'=>'']) ?>
    <?= $form->field($model, 'host')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'homepage')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'status')->dropdownList([''=>'',0=>Yii::t('app','Disabled'),1=>Yii::t('app','Enabled')]) ?>

    <div class="form-group mb-3 mt-3">
        <?= Html::submitButton('<i class="fas fa-search  mr-2"></i>' . Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('<i class="fas fa-broom mr-2"></i>' .Yii::t('app', 'Reset'), ['class' => 'btn btn-outline-secondary btn-reset']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    </div>
    <!--.col-md-12-->
</div>
