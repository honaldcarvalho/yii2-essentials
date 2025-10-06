<?php

use croacworks\essentials\models\State;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\custom\ServiceOriginSearch */
/* @var $form yii\widgets\ActiveForm */
$script = <<< JS
 $('#client-state_id').select2({width:'100%',allowClear:true,placeholder:'Select one state'});
 $('#client-city_id').select2({width:'100%',allowClear:true,placeholder:'Select one city'});
JS;
$this::registerJs($script);
?>
<div class="col-md-12">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <div class="row mt-2">
        <?php //$form->field($model, 'id') 
        ?>
        <div class="col-md-6">
            <?= $form->field($model, 'fullname')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'phone')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'identity_number')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'cpf_cnpj')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-2">
            <?= $form->field($model, 'state_id')->dropDownList(
                yii\helpers\ArrayHelper::map(State::find()
                    ->select('id,name')->asArray()->all(), 'id', 'name'),
                ['prompt' => '-- Select one state --']
            ) ?>
        </div>
        <div class="col-md-2">
            <?= $form->field($model, 'city_id')->dropDownList(
                yii\helpers\ArrayHelper::map(croacworks\essentials\models\City::find()
                    ->select('id,name')->asArray()->all(), 'id', 'name'),
                ['prompt' => '-- Select one city --']
            ) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'street')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-2">
            <?= $form->field($model, 'district')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-2">
            <?= $form->field($model, 'postal_code')->textInput(['maxlength' => true]) ?>
        </div>

        <div class="form-group">
            <?= Html::submitButton('<i class="fas fa-search  mr-2"></i>' . Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
            <?= Html::resetButton('<i class="fas fa-broom mr-2"></i>' .Yii::t('app', 'Reset'), ['class' => 'btn btn-outline-secondary btn-reset']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
    <!--.col-md-12-->
</div>