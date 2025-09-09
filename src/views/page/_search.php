<?php

use croacworks\essentials\models\Language;
use croacworks\essentials\models\PageSection;
use yii\helpers\Html;
use croacworks\essentials\widgets\form\ActiveForm;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\PageSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="row mt-2">
    <div class="col-md-12">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'page_section_id')->dropDownList(
        yii\helpers\ArrayHelper::map(PageSection::find()->all(), 'id', 'name'), 
        ['prompt' => '-- selecione uma secção --']) ?>

    <?= $form->field($model, 'language_id')->dropDownList(
        yii\helpers\ArrayHelper::map(Language::find()->all(), 'id', 'name'), 
        ['prompt' => '-- selecione uma lingua --']) ?>

    <?= $form->field($model, 'slug') ?>

    <?= $form->field($model, 'title') ?>

    <?= $form->field($model, 'description') ?>

    <?php // echo $form->field($model, 'content') ?>

    <?php // echo $form->field($model, 'keywords') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?= $form->field($model, 'status')->dropdownList([''=>'',0=>Yii::t('app','Disabled'),1=>Yii::t('app','Enabled')]) ?>

    <div class="form-group mb-3 mt-3">
        <?= Html::submitButton('<i class="fas fa-search  mr-2"></i>' . Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('<i class="fas fa-broom mr-2"></i>' .Yii::t('app', 'Reset'), ['class' => 'btn btn-outline-secondary btn-reset']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    </div>
    <!--.col-md-12-->
</div>
