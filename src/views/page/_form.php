<?php

use croacworks\essentials\models\Language;
use croacworks\essentials\models\PageSection;
use yii\helpers\Html;
use croacworks\essentials\widgets\form\ActiveForm;
use croacworks\essentials\widgets\form\TinyMCE;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\Page $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="page-form">

    <?php $form = ActiveForm::begin(); ?>
    
    <?= $form->field($model, 'group_id')->dropDownList(yii\helpers\ArrayHelper::map(croacworks\essentials\models\Group::find()
    ->asArray()->all(), 'id', 'name'), ['class'=>'form-control'])->label(Yii::t('app','Group')) ?>

    <?= $form->field($model, 'page_section_id')->dropDownList(
        yii\helpers\ArrayHelper::map(PageSection::find()->all(), 'id', 'name'), 
        ['prompt' => '-- selecione uma secção --']) ?>

    <?= $form->field($model, 'language_id')->dropDownList(
        yii\helpers\ArrayHelper::map(Language::find()->all(), 'id', 'name'), 
        ['prompt' => '-- selecione uma lingua --']) ?>

    <?= $form->field($model, 'slug')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'content')->widget(TinyMCE::class, [
        'options' => ['rows' => 20]
    ]); ?>

    <?= $form->field($model, 'custom_css')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'custom_js')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'keywords')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'status')->checkbox() ?>

    <div class="form-group mb-3 mt-3">
        <?= Html::submitButton('<i class="fas fa-save mr-2"></i>'.Yii::t('croacworks\essentials','Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
