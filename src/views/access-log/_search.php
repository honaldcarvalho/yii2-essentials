<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\AccessLogSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="row mb-3">
    <div class="col-md-6">
        <?php $form = ActiveForm::begin([
            'method' => 'get',
            'action' => ['index'],
        ]); ?>

        <div class="row">
            <div class="col-md-5">
                <?= Html::label(Yii::t('app', 'Start Date'), 'start_date') ?>
                <?= Html::input('date', 'start_date', $model->startDate, ['class' => 'form-control']) ?>
            </div>
            <div class="col-md-5">
                <?= Html::label(Yii::t('app', 'End Date'), 'end_date') ?>
                <?= Html::input('date', 'end_date', $model->endDate, ['class' => 'form-control']) ?>
            </div>
            <div class="col-md-2 align-self-end">
                <?= Html::submitButton(Yii::t('app', 'Filter'), ['class' => 'btn btn-primary']) ?>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>