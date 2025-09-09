<?php

use croacworks\essentials\models\Group;
use croacworks\essentials\models\LicenseType;
use yii\helpers\Html;
use croacworks\essentials\widgets\form\ActiveForm;

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

    <?= $form->field($model, 'license_type_id')->dropDownList(yii\helpers\ArrayHelper::map(LicenseType::find()
            ->select('id,name')->asArray()->all(), 
            'id', 'name'),['prompt'=>' -- License Type --']) ?>

    <?= $form->field($model, 'group_id')->dropDownList(yii\helpers\ArrayHelper::map(Group::find()
            ->select('id,name')->asArray()->all(), 
            'id', 'name'),['prompt'=>' -- Group --']) ?>

    <?= $form->field($model, 'validate')->search('date', '>='); ?>

    <?= $form->field($model, 'created_at')->search('date', '>='); ?>

    <?= $form->field($model, 'status')->dropdownList([''=>'',0=>Yii::t('app','Disabled'),1=>Yii::t('app','Enabled')]) ?>

    <div class="form-group mb-3">
        <?= Html::submitButton('<i class="fas fa-search  mr-2"></i>' . Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('<i class="fas fa-broom mr-2"></i>' .Yii::t('app', 'Reset'), ['class' => 'btn btn-outline-secondary btn-reset']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    </div>
    <!--.col-md-12-->
</div>
