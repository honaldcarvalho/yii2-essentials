<?php

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\Role $model */
/** @var yii\widgets\ActiveForm $form */

use croacworks\essentials\models\Group;;
use croacworks\essentials\models\User;
use croacworks\essentials\themes\coreui\assets\PluginAsset;
use yii\helpers\Html;
use yii\web\View;
use croacworks\essentials\widgets\form\ActiveForm;

?>

<div class="role-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'group_id')->dropDownList(yii\helpers\ArrayHelper::map(Group::find()->asArray()->all(), 'id', 'name'), ['prompt' => '-- selecione um grupo --']) ?>

    <?= $form->field($model, 'user_id')->dropDownList(yii\helpers\ArrayHelper::map(User::find()->select('id,username')->asArray()->all(), 'id', 'username'), ['prompt' => '-- selecione um usuario --']) ?>

    <?= $form->field($model, 'controller')->textInput() ?>

    <?= $form->field($model, 'path')->textInput() ?>

    <?= $form->field($model, 'actions')->textarea() ?>

    <?= $form->field($model, 'status')->checkbox() ?>

    <div class="form-group mb-3">
        <?= Html::submitButton('<i class="fas fa-save mr-2"></i>'.Yii::t('app','Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>