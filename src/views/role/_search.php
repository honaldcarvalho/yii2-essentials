<?php

use croacworks\essentials\controllers\RoleController;
use croacworks\essentials\models\Group;
use croacworks\essentials\models\User;
use yii\helpers\Html;
use croacworks\essentials\widgets\form\ActiveForm;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\RoleSearch */
/* @var $form yii\widgets\ActiveForm */
$controllers = RoleController::getAllControllersRestricted();
$js = <<<JS
$(function () {
    $('#role-controller').select2({width:'100%',allowClear:true,placeholder:'-- Select one Controller --'});
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

    <?= $form->field($model, 'group_id')->dropDownList(yii\helpers\ArrayHelper::map(Group::find()->asArray()->all(), 'id', 'name'), ['prompt' => '-- selecione um grupo --']) ?>

    <?= $form->field($model, 'user_id')->dropDownList(yii\helpers\ArrayHelper::map(User::find()->select('id,username')->asArray()->all(), 'id', 'username'), ['prompt' => '-- selecione um usuario --']) ?>

    <?= $form->field($model, 'controller')->dropDownList($controllers, [
        'multiple' => false,
        'prompt' => '-- CONTROLLER --',
    ]) ?>
    
    <?= $form->field($model, 'status')->dropdownList([''=>'',0=>Yii::t('app','Disabled'),1=>Yii::t('app','Enabled')]) ?>

    <div class="form-group">
        <?= Html::submitButton('<i class="fas fa-search  mr-2"></i>' . Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('<i class="fas fa-broom mr-2"></i>' .Yii::t('app', 'Reset'), ['class' => 'btn btn-outline-secondary btn-reset']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    </div>
    <!--.col-md-12-->
</div>
