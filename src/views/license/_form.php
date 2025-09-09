<?php

use croacworks\essentials\models\Group;;
use croacworks\essentials\models\LicenseType;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\License */
/* @var $form yii\bootstrap5\ActiveForm */
$script = <<<JS
function atualizarDataValidade(anos) {
    var hoje = new Date();
    hoje.setFullYear(hoje.getFullYear() + anos);
    var dataFormatada = hoje.toISOString().split('T')[0];
    $('#license-validate').val(dataFormatada);
}

// Valor inicial
var anos = 1;
atualizarDataValidade(anos);

$('#btn-maior').on('click', function() {
    anos++;
    $('#anos-validade').val(anos);
    atualizarDataValidade(anos);
});

$('#btn-menor').on('click', function() {
    if (anos > 1) {
        anos--;
        $('#anos-validade').val(anos);
        atualizarDataValidade(anos);
    }
});
JS;
$this->registerJs($script);
?>

<div class="license-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'license_type_id')->dropDownList(yii\helpers\ArrayHelper::map(LicenseType::find()
            ->select('id,name')->asArray()->all(), 
            'id', 'name'),['prompt'=>' -- License Type --']) ?>

    <?= $form->field($model, 'group_id')->dropDownList(yii\helpers\ArrayHelper::map(Group::find()
            ->select('id,name')->asArray()->all(), 
            'id', 'name'),['prompt'=>' -- Group --']) ?>

    <div class="mb-3">
        <label class="form-label">Validade (em anos)</label>
        <div class="input-group">
            <button class="btn btn-outline-secondary" type="button" id="btn-menor">−</button>
            <input type="text" class="form-control text-center" id="anos-validade" value="1" readonly>
            <button class="btn btn-outline-secondary" type="button" id="btn-maior">+</button>
        </div>
    </div>

    <?= $form->field($model, 'validate')->input('date') ?>

    <?= $form->field($model, 'status')->checkbox() ?>

    <div class="form-group mb-3">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
