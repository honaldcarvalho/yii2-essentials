<?php

use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\models\Language;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\User $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="user-form">

    <?php $form = ActiveForm::begin(); ?>

    <?=  AuthorizationController::isAdmin() ? $form->field($model, 'group_id')->dropDownList(yii\helpers\ArrayHelper::map(
    $groups, 'id', 'name'), ['prompt' => '-- Selecione um Grupo --']) : ''?>
    
    <?= $form->field($model, 'email')->textInput() ?>

    <?= $form->field($model, 'password')->passwordInput() ?>
    <?= $form->field($model, 'password_confirm')->passwordInput() ?>

    <?= AuthorizationController::isAdmin() ? $form->field($model, 'status')->dropDownList([9 => Yii::t('app', 'Inactive'), 10 => Yii::t('app', 'Active'), 20 => Yii::t('app', 'No system user')]) : '' ?>

    <div class="form-group">
        <?= Html::submitButton('<i class="fas fa-save mr-2"></i>' . Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>