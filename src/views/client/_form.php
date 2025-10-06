<?php

use croacworks\essentials\models\City;
use croacworks\essentials\models\State;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model app\models\custom\ServiceOrigin */
/* @var $form yii\bootstrap5\ActiveForm */

// Get the initial saved city data (note $model->city is an array of city ids)
//$dataList = City::find()->andWhere(['id' => $model->city])->all();

$city = 0;
$state_id = null;
$cityList = [];
$disable_city = 1;

if($model->city_id !== null){
    $dataList = City::find()->all();
    $cityList = ArrayHelper::map($dataList, 'id', 'name');
    $city = $model->city_id;
    $state_id = $model->city->state_id;
    $disable_city = 0;
}

$script = <<< JS
    function getCities() {
        $('#overlay_city').show();
        $.ajax({
            type: "POST",
            dataType: "json",
            url: "/rest/address/cities",
            dataType: "json",
            'Content-Type': 'application/json',
            data: { "state_id": $('#client-state_id').val()}
        }).done(function (response) {

            $('#client-city_id').select2('destroy');
            $('#client-city_id').html('');

            $.each(response, function (i, item) {
                $('#client-city_id').append($('<option>', { 
                    value: item.id,
                    text : item.name
                }));

            });

            if($city != 0){
                $('#client-city_id option[value=$city]').attr('selected','selected');
            }

            $('#client-city_id').prop('disabled',false);
            $('#client-city_id').select2();

        }).fail(function (response) {
            toastr.error("Fail " + response.status+": " + response.statusText);
        }).always(function (statusCode) {
            $('#overlay_city').hide();
        });
    }

    $('#client-state_id').change(function(){
        getCities();
    });

    let loadCity = `
    <div id="overlay_city" class="overlay" style="height: 100%;position: absolute;width: 100%;z-index: 3000;display:none;top:0;left:0;">
        <div class="fa-3x">
            <i class="fas fa-sync fa-spin"></i>
        </div>
    </div>`;
    $('.field-client-city_id').append(loadCity);
    $('.field-client-city_id').addClass('position-relative');
    $('#client-city_id').prop('disabled',{$disable_city});
    $('#client-state_id').select2({width:'100%',allowClear:true,placeholder:'Select one state'});
    $('#client-city_id').select2({width:'100%',allowClear:true,placeholder:'Select one city'});
JS;

$this::registerJs($script);

?>

<div class="client-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'nickname')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'fullname')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'email')->input('email', ['maxlength' => true]) ?>

    <?= $form->field($model, 'phone')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'identity_number')->input('number', ['maxlength' => true]) ?>

    <?= $form->field($model, 'cpf_cnpj')->textInput(['maxlength' => true]) ?>
   
    <?= $form->field($model, 'state_id')->dropDownList(yii\helpers\ArrayHelper::map(State::find()
        ->select('id,name')->asArray()->all(), 'id', 'name'), 
        ['prompt' => '-- Select one state --']) ?>

    <?= $form->field($model, 'city_id')->dropdownList($cityList,['prompt' => '-- Select first one state --']) ?>

    <?= $form->field($model, 'street')->textInput(['maxlength' => true]) ?>
    
    <?= $form->field($model, 'district')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'number')->textInput() ?>

    <?= $form->field($model, 'postal_code')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'address_complement')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'notes')->textarea(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>