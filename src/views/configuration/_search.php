<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

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

    <?= $form->field($model, 'description') ?>

    <?= $form->field($model, 'file_id') ?>

    <?= $form->field($model, 'meta_viewport') ?>

    <?= $form->field($model, 'meta_author') ?>

    <?php // echo $form->field($model, 'meta_robots') ?>

    <?php // echo $form->field($model, 'meta_googlebot') ?>

    <?php // echo $form->field($model, 'meta_keywords') ?>

    <?php // echo $form->field($model, 'meta_description') ?>

    <?php // echo $form->field($model, 'canonical') ?>

    <?php // echo $form->field($model, 'host') ?>

    <?php // echo $form->field($model, 'title') ?>

    <?php // echo $form->field($model, 'bussiness_name') ?>

    <?php // echo $form->field($model, 'email') ?>

    <?php // echo $form->field($model, 'fone') ?>

    <?php // echo $form->field($model, 'address') ?>

    <?php // echo $form->field($model, 'postal_code') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'status') ?>

    <div class="form-group">
        <?= Html::submitButton('<i class="fas fa-search  mr-2"></i>' . Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('<i class="fas fa-broom mr-2"></i>' .Yii::t('app', 'Reset'), ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    </div>
    <!--.col-md-12-->
</div>
