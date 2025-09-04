<?php
/** @var yii\web\View $this */
/** @var croacworks\essentials\models\RoleTemplate $model */
/** @var yii\widgets\ActiveForm $form */

use croacworks\essentials\controllers\RoleController; // pode reutilizar este helper
use croacworks\essentials\themes\coreui\assets\PluginAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

PluginAsset::register($this)->add(['multiselect']);

$controllers = RoleController::getAllControllersRestricted();
$actionUrl      = Url::to(['role/get-actions']);

$origins        = [];
$savedActions   = [];
$availableActions = [];
$fromActions    = [];
$toActions      = [];

// Pré-carrega dados quando editando
if (!$model->isNewRecord) {
    // origins em array (campo é string com ';')
    if (!empty($model->origin)) {
        foreach (explode(';', $model->origin) as $o) {
            $o = trim($o);
            if ($o !== '') $origins[$o] = $o;
        }
    }

    // actions salvas
    $savedActions = $model->actions ? array_values(array_filter(array_map('trim', explode(';', $model->actions)))) : [];

    // Descobre actions do controller atual
    $controllerFQCN = $model->controller;
    if ($controllerFQCN && class_exists($controllerFQCN)) {
        $methods = get_class_methods($controllerFQCN);
        $availableActions = array_filter($methods, fn($m) => str_starts_with($m, 'action'));
        // remove prefixo "action" e converte camelCase para id (ex.: actionGetItems -> get-items)
        $availableActions = array_map(fn($a) => \yii\helpers\Inflector::camel2id(substr($a, 6)), $availableActions);
        sort($availableActions);
    }

    // dual list
    $fromActions = array_values(array_diff($availableActions, $savedActions));
    $toActions   = $savedActions;
}

$js = <<<JS
(function(){
    // inicializa multiselect das duas listas
    // (o plugin usado pelo PluginAsset 'multiselect' espera estes IDs)
    $('#multiselect').multiselect();
    $('#multiselect_to').multiselect();

    // Select2
    $('#roletemplate-controller').select2({width:'100%',allowClear:true,placeholder:'-- Select one Controller --'});
    $('#roletemplate-origin').select2({width:'100%',allowClear:true,placeholder:'',multiple:true});
    $('#roletemplate-level').select2({width:'100%',allowClear:true,placeholder:'-- Level --'});

    // Buscar actions do controller via AJAX
    $('#roletemplate-controller').on('change', function () {
        let controller = $(this).val();
        $('#multiselect').empty();
        $('#multiselect_to').empty();

        if (!controller) return;

        $.post('{$actionUrl}', { controller }, function(res) {
            if (res && res.success) {
                let options = '';
                (res.actions || []).forEach(function(action) {
                    options += `<option value="\${action}">\${action}</option>`;
                });
                $('#multiselect').html(options);
            } else {
                Swal.fire("Erro", (res && res.message) ? res.message : "Não foi possível carregar as actions", "error");
            }
        }, 'json');
    });

    // Enter para adicionar origem no Select2
    $('#add_origin').on('keyup', function(event){
        if (event.keyCode === 13) {
            let val = $('#add_origin').val().trim();
            if (!val) return;

            // cria opção dinâmica no select2 múltiplo
            let option = new Option(val, val, true, true);
            $('#roletemplate-origin').append(option).trigger('change');
            $('#add_origin').val('');
        }
    });

    // Botões do dual list
    $('#multiselect_rightAll').on('click', function(){
        $('#multiselect option').appendTo('#multiselect_to');
    });
    $('#multiselect_rightSelected').on('click', function(){
        $('#multiselect option:selected').appendTo('#multiselect_to');
    });
    $('#multiselect_leftSelected').on('click', function(){
        $('#multiselect_to option:selected').appendTo('#multiselect');
    });
    $('#multiselect_leftAll').on('click', function(){
        $('#multiselect_to option').appendTo('#multiselect');
    });

    // Antes de enviar o form: empacota actions (lista "to") em string separada por ';'
    $('#roletemplate-form').on('submit', function(){
        let selected = [];
        $('#multiselect_to option').each(function(){
            selected.push($(this).val());
        });
        $('#roletemplate-actions-hidden').val(selected.join(';'));

        // Também empacota origins se necessário (caso backend espere string)
        // Se o backend já aceita array e faz implode, pode remover este bloco.
        let origins = $('#roletemplate-origin').val() || [];
        $('#roletemplate-origin-hidden').val(origins.join(';'));
    });
})();
JS;

$this->registerJs($js);
?>

<div class="role-template-form">
    <?php $form = ActiveForm::begin(['id' => 'roletemplate-form']); ?>

    <!-- LEVEL -->
    <?= $form->field($model, 'level')->dropDownList(\croacworks\essentials\models\RoleTemplate::optsLevel(), [
        'prompt' => '-- Level --'
    ]) ?>

    <!-- CONTROLLER -->
    <?= $form->field($model, 'controller')->dropDownList($controllers, [
        'multiple' => false,
        'prompt'   => '-- CONTROLLER --',
    ]) ?>

    <!-- ORIGINS (múltiplo) + input para adicionar via Enter) -->
    <div class="mb-3">
        <?= Html::label(Yii::t('app', 'Enter origin and press Enter:'), 'add_origin') ?>
        <?= Html::textInput('add_origin', '', ['id' => 'add_origin', 'class' => 'form-control', 'autocomplete'=>'off']) ?>
    </div>

    <?php
    // Campo principal de origins como múltiplo (Select2). O atributo no model é string;
    // se preferir armazenar array no POST e tratar no controller, deixe como está.
    echo $form->field($model, 'origin')->dropDownList(
        $origins,
        ['multiple' => true, 'id' => 'roletemplate-origin']
    )->label('Origins');

    // Hidden opcional para enviar origins já “implodidos” (se seu backend esperar string direta).
    echo Html::hiddenInput('RoleTemplate[originHidden]', '', ['id' => 'roletemplate-origin-hidden']);
    ?>

    <!-- ACTIONS Dual List -->
    <div class="form-group">
        <div class="row">
            <div class="col-md-5">
                <label for="multiselect"><?= Yii::t('app', 'Available actions') ?></label>
                <select name="from[]" id="multiselect" class="form-control" size="10" multiple="multiple">
                    <?php foreach ($fromActions as $action): ?>
                        <option value="<?= Html::encode($action) ?>"><?= Html::encode($action) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 d-flex flex-column justify-content-center gap-2">
                <button type="button" id="multiselect_rightAll" class="btn btn-outline-secondary mb-2"><i class="fas fa-forward"></i></button>
                <button type="button" id="multiselect_rightSelected" class="btn btn-outline-secondary mb-2"><i class="fas fa-chevron-right"></i></button>
                <button type="button" id="multiselect_leftSelected" class="btn btn-outline-secondary mb-2"><i class="fas fa-chevron-left"></i></button>
                <button type="button" id="multiselect_leftAll" class="btn btn-outline-secondary"><i class="fas fa-backward"></i></button>
            </div>

            <div class="col-md-5">
                <label for="multiselect_to"><?= Yii::t('app', 'Selected actions') ?></label>
                <select name="to[]" id="multiselect_to" class="form-control" size="10" multiple="multiple">
                    <?php foreach ($toActions as $action): ?>
                        <option value="<?= Html::encode($action) ?>"><?= Html::encode($action) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <?php
    // Hidden para salvar as actions selecionadas já concatenadas com ';'
    echo Html::activeHiddenInput($model, 'actions', ['id' => 'roletemplate-actions-hidden']);
    ?>

    <!-- STATUS -->
    <?= $form->field($model, 'status')->checkbox() ?>

    <div class="form-group mt-3">
        <?= Html::submitButton('<i class="fas fa-save me-2"></i>' . Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
