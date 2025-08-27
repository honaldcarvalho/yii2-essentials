<?php

use croacworks\essentials\controllers\CommonController;
use croacworks\essentials\controllers\RoleController;
use croacworks\essentials\models\SysMenu;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\SysMenu */
/* @var $form yii\bootstrap5\ActiveForm */

$assetsDir = CommonController::getAssetsDir();
$controllers = RoleController::getAllControllers(); // FQCNs
$actionUrl = Url::to(['/role/get-actions']); // AJAX para buscar actions

$script = <<< JS

async function populateDropdown() {

    const response = await fetch('{$assetsDir}/plugins/fontawesome-free/list.json');
    const iconList = await response.json();

    const dropdown = document.getElementById('menu-icon');

    iconList.forEach(icon => {
        $('#menu-icon').append(`<option data-icon="\${icon}" value="\${icon}">\${icon}</option>`);
    });
}

function iformat(icon) {
    var originalOption = icon.element;
    return $('<span><i class="' + $(originalOption).data('icon') + '"></i> ' + icon.text + '</span>');
}

populateDropdown().then(iconsArray => {

    $('#menu-icon').select2({
        width: "100%",
        templateSelection: iformat,
        templateResult: iformat,
        escapeMarkup: function(m) {
            return m;
        }
    });

    $('#menu-icon').val("{$model->icon}").trigger("change");
});

$('#controller-select').select2({ width: '100%', placeholder: 'Selecione um controller' });
$('#action-select').select2({ width: '100%', placeholder: 'Selecione uma action' });

function updateVisibleField() {
    let controller = $('#controller-select').val();
    let action = $('#action-select').val();
    if (controller && action) {
        $('#menu-visible').val(controller + ';' + action);
    }
}

$('#controller-select').on('change', function() {
    let controller = $(this).val();
    $('#action-select').html('');
    if (!controller) return;

    $.post('{$actionUrl}', { controller }, function(res) {
        if (res.success) {
            let options = '<option></option>';
            res.actions.forEach(function(action) {
                options += `<option value=\"\${action}\">\${action}</option>`;
            });
            $('#action-select').html(options).trigger('change');
        }
    }, 'json');
});

$('#action-select').on('change', updateVisibleField);

JS;

$this->registerJs($script);

?>

<div class="menu-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'menu_id')->widget(\kartik\select2\Select2::classname(), [
                                'data' => yii\helpers\ArrayHelper::map(SysMenu::find()
                                ->asArray()->all(),'id','label'),
                                'options' => ['multiple' => false, 'placeholder' => Yii::t('app','Select Menu')],
                                'pluginOptions' => [
                                    'allowClear' => true,
                                    'width'=>'100%',
                                ],
                            ])->label('Menu');
                        ?>


    <?= $form->field($model, 'controller')->dropDownList($controllers, [
        'id' => 'controller-select',
        'prompt' => '-- Selecione o controller --'
    ])->label('Controller') ?>

    <?= $form->field($model, 'action')->dropDownList([], [
        'id' => 'action-select',
        'prompt' => '-- Selecione a action --'
    ])->label('Action') ?>

    <?= $form->field($model, 'label')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'icon')->dropDownList([], ['prompt' => '-- Selecione um Icone --']) ?>
    
    <?= $form->field($model, 'icon_style')->textInput(['maxlength' => true,'value'=>$model->isNewRecord ? 'fas' : $model->path]) ?>

    <?= $form->field($model, 'visible')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'url')->textInput(['maxlength' => true,'value'=>$model->isNewRecord ? '#' : $model->url]) ?>

    <?= $form->field($model, 'path')->textInput(['maxlength' => true,'value'=>$model->isNewRecord ? 'app' : $model->path]) ?>

    <?= $form->field($model, 'order')->input('number') ?>

    <?= $form->field($model, 'active')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'status')->checkbox() ?>

    <div class="form-group">
        <?= Html::submitButton('<i class="fas fa-save mr-2"></i>'.Yii::t('app','Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
