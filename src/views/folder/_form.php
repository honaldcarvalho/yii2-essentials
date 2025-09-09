<?php

use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\models\Group;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\Folder */
/* @var $form yii\bootstrap5\ActiveForm */
?>

<div class="folder-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'group_id')->dropDownList(yii\helpers\ArrayHelper::map(Group::find()->asArray()->all(), 'id', 'name'), ['prompt' => '-- selecione um grupo --']) ?>
    
    <?= $form->field($model, 'folder_id')->dropDownList(yii\helpers\ArrayHelper::map(croacworks\essentials\models\Folder::find()
    ->where(['in','group_id', AuthController::userGroups()])
    ->asArray()->all(), 'id', 'name'), ['class'=>'form-control', 'prompt' => Yii::t('app','-- Select Folder --')]) ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'external')->checkbox() ?>

    <?= $form->field($model, 'status')->checkbox() ?>

    <div class="form-group mb-3 mt-3">
        <?= Html::submitButton('<i class="fas fa-save mr-2"></i>'.Yii::t('app','Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
