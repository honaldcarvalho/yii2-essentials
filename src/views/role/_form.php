<?php

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\Role $model */
/** @var yii\widgets\ActiveForm $form */

use croacworks\essentials\controllers\RoleController;
use croacworks\essentials\models\Group;;

use croacworks\essentials\models\User;
use croacworks\essentials\themes\coreui\assets\PluginAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ActiveForm;

$origins = [];
$savedActions = [];
$availableActions = [];
$fromActions  = [];
$toActions  = [];

PluginAsset::register($this)->add(['multiselect']);
$controllers = RoleController::getAllControllersRestricted();
$actionUrl = Url::to(['get-actions']);

if(!$model->isNewRecord){
    foreach( explode(';',$model->origin) as $origin){
        $origins[$origin] = $origin;
    } 
    // Parse actions salvas
    $savedActions = $model->actions ? explode(';', $model->actions) : [];

    // Garante que o controller foi carregado e é válido
    $controllerFQCN = $model->controller;

    ; // crie esse método se necessário, baseado no `path:controller`
    $availableActions = [];

    if (class_exists($controllerFQCN)) {
        $methods = get_class_methods($controllerFQCN);
        $availableActions = array_filter($methods, fn($m) => str_starts_with($m, 'action'));
        $availableActions = array_map(fn($a) => \yii\helpers\Inflector::camel2id(substr($a, 6)), $availableActions);
    }

    // Separar actions em usadas e não usadas
    $fromActions = array_diff($availableActions, $savedActions);
    $toActions = $savedActions;

}


$js = <<<JS
$(function () {
    // Inicializa multiselect
    $('#multiselect').multiselect();

    $('#role-controller').on('change', function () {
        let controller = $(this).val();
        $('#multiselect').html('');
        $('#multiselect_to').html('');

        if (!controller) return;

        $.post('{$actionUrl}', { controller }, function(res) {
            if (res.success) {
                let options = '';
                res.actions.forEach(function(action) {
                    options += `<option value="\${action}">\${action}</option>`;
                });
                $('#multiselect').html(options);
            } else {
                Swal.fire("Erro", res.message || "Não foi possível carregar as actions", "error");
            }
        }, 'json');
    });
        $('#add_origin').keyup(function( event ) {

            if (event.keyCode === 13) {
                 var newOption = new Option($('#add_origin').val(),$('#add_origin').val(), true, true);
                 $('#role-origin').append(newOption).trigger('change');
                 //$('#role-origin').val(null).trigger('change');
                 $('#add_origin').val('');
            }
        });
        
    $('#role-controller').select2({width:'100%',allowClear:true,placeholder:'-- Select one Controller --'});
    $('#role-origin').select2({width:'100%',allowClear:true,placeholder:'',multiple:true});
});
JS;

$this->registerJs($js);

?>

<div class="role-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'group_id')->dropDownList(yii\helpers\ArrayHelper::map(Group::find()->asArray()->all(), 'id', 'name'), ['prompt' => '-- selecione um grupo --']) ?>

    <?= $form->field($model, 'user_id')->dropDownList(yii\helpers\ArrayHelper::map(User::find()->select('id,username')->asArray()->all(), 'id', 'username'), ['prompt' => '-- selecione um usuario --']) ?>

    <?= $form->field($model, 'controller')->dropDownList($controllers, [
        'multiple' => false,
        'prompt' => '-- CONTROLLER --',
    ]) ?>
    
    <div id="actions" class="form-group">
        <?= Html::label(Yii::t('app', 'Enter origin and digit enter:'), 'add_origin') ?>
        <?= Html::textInput('add_origin', '', ['id' => 'add_origin', 'class' => 'form-control']) ?>
    </div>

    <?= $form->field($model, 'origin[]', ['enableClientValidation' => false])->dropDownList($origins)->label('Origins');
    ?>

    <div id="actions" class="form-group">
        <div class="row">
            <div class="col-md-5">
                <select name="from[]" id="multiselect" class="form-control" size="8" multiple="multiple">
                <?php foreach ($fromActions as $action): ?>
                    <option value="<?= $action ?>"><?= $action ?></option>
                <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="button" id="multiselect_rightAll" class="btn btn-block"><i class="fas fa-forward"></i></button>
                <button type="button" id="multiselect_rightSelected" class="btn btn-block"><i class="fas fa-chevron-right"></i></button>
                <button type="button" id="multiselect_leftSelected" class="btn btn-block"><i class="fas fa-chevron-left"></i></i></button>
                <button type="button" id="multiselect_leftAll" class="btn btn-block"><i class="fas fa-backward"></i></button>
            </div>
            <div class="col-md-5">
                <select name="to[]" id="multiselect_to" class="form-control" size="8" multiple="multiple">
                <?php foreach ($toActions as $action): ?>
                    <option value="<?= $action ?>"><?= $action ?></option>
                <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <?= $form->field($model, 'status')->checkbox() ?>

    <div class="form-group">
        <?= Html::submitButton('<i class="fas fa-save mr-2"></i>' . Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>