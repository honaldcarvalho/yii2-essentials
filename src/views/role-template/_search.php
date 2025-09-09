<?php

use croacworks\essentials\controllers\RoleController;
use yii\helpers\Html;
use croacworks\essentials\widgets\form\ActiveForm;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\RoleTemplate */
/* @var $form yii\widgets\ActiveForm */

$controllers = RoleController::getAllControllersRestricted();
$js = <<<JS
$(function () {
    $('#roletemplate-controller').select2({width:'100%',allowClear:true,placeholder:'-- Select one Controller --'});
});
JS;

$this->registerJs($js);
?>

<div class="row mt-2">
    <div class="col-md-12">

    <?php $form = croacworks\essentials\widgets\form\ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'level')->dropDownList(\croacworks\essentials\models\RoleTemplate::optsLevel(), [
        'prompt' => '-- Level --'
    ]) ?>

    <?= $form->field($model, 'controller')->dropDownList($controllers, [
        'multiple' => false,
        'prompt' => '-- CONTROLLER --',
    ]) ?>
    
    <?= $form->field($model, 'status')->dropdownList([''=>'',0=>Yii::t('app','Disabled'),1=>Yii::t('app','Enabled')]) ?>

    <div class="form-group mb-3">
        <?= Html::submitButton('<i class="fas fa-search  mr-2"></i>' . Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('<i class="fas fa-broom mr-2"></i>' .Yii::t('app', 'Reset'), ['class' => 'btn btn-outline-secondary btn-reset']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    </div>
    <!--.col-md-12-->
</div>
