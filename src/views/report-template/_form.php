<?php

use croacworks\essentials\widgets\AceEditor;
use croacworks\essentials\widgets\TinyMCE;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\ReportTemplate $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="report-template-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'group_id')->dropDownList(yii\helpers\ArrayHelper::map(croacworks\essentials\models\Group::find()
    ->asArray()->all(), 'id', 'name'), ['class'=>'form-control'])->label(Yii::t('app','Group')) ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>
    
    <div class="row">
        <div class="col-md-3">
            <?= $form->field($model, 'format')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'margin_top')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'margin_bottom')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'margin_left')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'margin_right')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'margin_header')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'margin_footer')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'setAutoTopMargin')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'setAutoBottomMargin')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

    <?= $form->field($model, 'header_html')->widget(TinyMCE::class, [
        'options' => ['rows' => 20],
        'content_style' => $model->style,
    ]); ?>
    
    <?= $form->field($model, 'body_html')->widget(TinyMCE::class, [
        'options' => ['rows' => 20],
        'cleanup' => false,
        'verify_html' => false,
        'content_style' => $model->style,
    ]); ?>
    
    <?= $form->field($model, 'footer_html')->widget(TinyMCE::class, [
        'options' => ['rows' => 20],
        'content_style' => $model->style,
    ]); ?>

    <?= $form->field($model, 'style')->widget(AceEditor::class, [
        'options' => ['rows' => 20]
    ]); ?>

    <?= $form->field($model, 'status')->checkbox() ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
