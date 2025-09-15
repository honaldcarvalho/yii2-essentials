<?php
/** @var yii\web\View $this */
/** @var croacworks\essentials\models\RoleTemplate $model */
/** @var yii\widgets\ActiveForm $form */

use croacworks\essentials\controllers\RoleController; // helper reutilizado
use croacworks\essentials\themes\coreui\assets\PluginAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use croacworks\essentials\widgets\form\ActiveForm;

PluginAsset::register($this)->add(['multiselect']);

$controllers      = RoleController::getAllControllersRestricted();
$actionUrl        = Url::to(['role/get-actions']);

$origins          = [];
$savedActions     = [];
$availableActions = [];
$originMap        = []; // id cru => ControllerShortName
$fromActions      = [];
$toActions        = [];

/** Pré-carrega dados quando editando */
if (!$model->isNewRecord) {
    // origins em array (campo é string com ';')
    if (!empty($model->origin)) {
        foreach (explode(';', $model->origin) as $o) {
            $o = trim($o);
            if ($o !== '') $origins[$o] = $o;
        }
    }

    // actions salvas (podem estar qualificadas ou cruas)
    $savedActions = $model->actions ? array_values(array_filter(array_map('trim', explode(';', $model->actions)))) : [];

    // Descobre actions do controller atual (ids crus + mapa de origem)
    $controllerFQCN = $model->controller;
    if ($controllerFQCN && class_exists($controllerFQCN)) {
        $res = RoleController::collectControllerActions($controllerFQCN, true);
        $availableActions = $res['list'];     // ['index','create',...]
        $originMap        = $res['origins'];  // ['index'=>'AuthorizationController', ...]
        sort($availableActions, SORT_NATURAL);
    }

    // Normaliza: se alguma action salva vier qualificada (Controller\id), mantém só a parte após "\"
    if ($savedActions) {
        $savedActions = array_map(static function ($a) {
            $a = trim((string)$a);
            if ($a === '') return '';
            $pos = strrpos($a, '\\');
            return $pos !== false ? substr($a, $pos + 1) : $a;
        }, $savedActions);
        $savedActions = array_values(array_filter($savedActions, static fn($v) => $v !== ''));
    }

    // dual list
    $fromActions = array_values(array_diff($availableActions, $savedActions));
    $toActions   = $savedActions;
}

/** Helper para rotular com "ControllerShortName\action" */
$labelOf = function (string $id) use ($originMap): string {
    $decl = $originMap[$id] ?? '';
    return ($decl !== '' ? $decl . '\\' : '') . $id;
};

$js = <<<JS
(function(){
    // inicializa multiselect das duas listas
    $('#multiselect').multiselect();
    $('#multiselect_to').multiselect();

    // Select2
    $('#roletemplate-controller').select2({width:'100%',allowClear:true,placeholder:'-- Select one Controller --'});
    $('#roletemplate-origin').select2({width:'100%',allowClear:true,placeholder:'',multiple:true});
    $('#roletemplate-level').select2({width:'100%',allowClear:true,placeholder:'-- Level --'});

    function buildOptions(actions, origins) {
        var html = '';
        (actions || []).forEach(function(id){
            var decl  = (origins && origins[id]) ? origins[id] : '';
            var label = (decl ? decl + '\\\\' : '') + id; // duplica a barra no JS string
            html += '<option value="'+id+'" data-decl="'+decl+'" title="'+label+'">'+label+'</option>';
        });
        return html;
    }

    // Buscar actions do controller via AJAX (value cru; label qualificado)
    $('#roletemplate-controller').on('change', function () {
        var controller = $(this).val();
        $('#multiselect').empty();
        $('#multiselect_to').empty();

        if (!controller) return;

        $.post('{$actionUrl}', { controller: controller }, function(res) {
            if (res && res.success) {
                var options = buildOptions(res.actions, res.origins);
                $('#multiselect').html(options);
            } else {
                if (window.Swal) {
                    Swal.fire("Erro", (res && res.message) ? res.message : "Não foi possível carregar as actions", "error");
                } else {
                    alert((res && res.message) ? res.message : "Não foi possível carregar as actions");
                }
            }
        }, 'json').fail(function(xhr){
            if (window.Swal) {
                Swal.fire("Erro", xhr.responseText || "Falha na requisição", "error");
            } else {
                alert(xhr.responseText || "Falha na requisição");
            }
        });
    });

    // Enter para adicionar origem no Select2
    $('#add_origin').on('keyup', function(event){
        if (event.keyCode === 13) {
            var val = $('#add_origin').val().trim();
            if (!val) return;

            var option = new Option(val, val, true, true);
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
        var selected = [];
        $('#multiselect_to option').each(function(){
            selected.push($(this).val()); // sempre o id cru
        });
        $('#roletemplate-actions-hidden').val(selected.join(';'));

        // Também empacota origins, se necessário
        var origins = $('#roletemplate-origin').val() || [];
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
    // Campo principal de origins como múltiplo (Select2).
    echo $form->field($model, 'origin')->dropDownList(
        $origins,
        ['multiple' => true, 'id' => 'roletemplate-origin']
    )->label('Origins');

    // Hidden opcional para enviar origins já “implodidos”.
    echo Html::hiddenInput('RoleTemplate[originHidden]', '', ['id' => 'roletemplate-origin-hidden']);
    ?>

    <!-- ACTIONS Dual List -->
    <div class="form-group mb-3 mt-3">
        <div class="row">
            <div class="col-md-5">
                <label for="multiselect"><?= Yii::t('app', 'Available actions') ?></label>
                <select name="from[]" id="multiselect" class="form-control" size="10" multiple="multiple">
                    <?php foreach ($fromActions as $action): ?>
                        <option value="<?= Html::encode($action) ?>"
                                data-decl="<?= Html::encode($originMap[$action] ?? '') ?>"
                                title="<?= Html::encode($labelOf($action)) ?>">
                            <?= Html::encode($labelOf($action)) ?>
                        </option>
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
                        <option value="<?= Html::encode($action) ?>"
                                data-decl="<?= Html::encode($originMap[$action] ?? '') ?>"
                                title="<?= Html::encode($labelOf($action)) ?>">
                            <?= Html::encode($labelOf($action)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <?php
    // Hidden para salvar as actions selecionadas já concatenadas com ';' (ids crus)
    echo Html::activeHiddenInput($model, 'actions', ['id' => 'roletemplate-actions-hidden']);
    ?>

    <!-- STATUS -->
    <?= $form->field($model, 'status')->checkbox() ?>

    <div class="form-group mt-3">
        <?= Html::submitButton('<i class="fas fa-save me-2"></i>' . Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
