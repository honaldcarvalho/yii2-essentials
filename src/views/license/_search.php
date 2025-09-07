<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\LicenseSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="row mt-2">
    <div class="col-md-12">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'license_type_id') ?>

    <?= $form->field($model, 'group_id') ?>

    <?= $form->field($model, 'validate') ?>

    <?= $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?= $form->field($model, 'status')->dropdownList([''=>'',0=>Yii::t('app','Disabled'),1=>Yii::t('app','Enabled')]) ?>

    <div class="form-group">
        <?= Html::submitButton('<i class="fas fa-search  mr-2"></i>' . Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('<i class="fas fa-broom mr-2"></i>' .Yii::t('app', 'Reset'), ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    </div>
    <!--.col-md-12-->
</div>
