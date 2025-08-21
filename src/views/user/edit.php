<?php

use croacworks\essentials\models\Language;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\User $model */

$this->title = Yii::t('app', 'Update User: {name}', [
    'name' => $model->fullname,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->fullname, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                <div class="user-form">

                    <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'fullname')->textInput() ?>
                    <?= $form->field($model, 'email')->textInput() ?>    
                    <?= $form->field($model, 'language')->dropDownList(yii\helpers\ArrayHelper::map(Language::find()
                            ->select('code,name')->asArray()->all(), 
                            'code', 'name')) ?>
                    <?= $form->field($model, 'theme')->dropDownList(['light'=>'Light','dark'=>'Dark']) ?>
                    <?= $form->field($model, 'password')->passwordInput() ?>
                    <?= $form->field($model, 'password_confirm')->passwordInput() ?>


                    <div class="form-group">
                        <?= Html::submitButton('<i class="fas fa-save mr-2"></i>'.Yii::t('app','Save'), ['class' => 'btn btn-success']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>

                    </div>

                </div>
            </div>
        </div>
        <!--.card-body-->
    </div>
    <!--.card-->
</div>