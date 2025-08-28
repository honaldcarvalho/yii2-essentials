<?php

/** @var yii\web\View $this */
/** @var weebz\yii2basics\models\Role $model */
/** @var yii\widgets\ActiveForm $form */

use weebz\yii2basics\models\Group;;
use weebz\yii2basics\models\User;
use weebz\yii2basics\themes\adminlte3\assets\PluginAsset;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

?>

<div class="role-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'group_id')->dropDownList(yii\helpers\ArrayHelper::map(Group::find()->asArray()->all(), 'id', 'name'), ['prompt' => '-- selecione um grupo --']) ?>

    <?= $form->field($model, 'user_id')->dropDownList(yii\helpers\ArrayHelper::map(User::find()->select('id,username')->asArray()->all(), 'id', 'username'), ['prompt' => '-- selecione um usuario --']) ?>

    <?= $form->field($model, 'controller')->textInput() ?>

    <?= $form->field($model, 'path')->textInput() ?>

    <?= $form->field($model, 'actions')->textarea() ?>

    <?= $form->field($model, 'status')->checkbox() ?>

    <div class="form-group">
        <?= Html::submitButton('<i class="fas fa-save mr-2"></i>'.Yii::t('app','Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>