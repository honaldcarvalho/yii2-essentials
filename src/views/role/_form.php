<?php

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\Role $model */
/** @var yii\widgets\ActiveForm $form */

use croacworks\essentials\controllers\RoleController;
use croacworks\essentials\models\Group;
use croacworks\essentials\models\User;
use croacworks\essentials\themes\coreui\assets\PluginAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use croacworks\essentials\widgets\form\ActiveForm;

$origins = [];
$savedActions = [];
$availableActions = [];
$originMap = [];
$fromActions  = [];
$toActions    = [];

PluginAsset::register($this)->add(['multiselect']);
$controllers = RoleController::getAllControllersRestricted();
$actionUrl   = Url::to(['get-actions']);

if (!$model->isNewRecord) {
    // origins existentes (mantidos)
    foreach (explode(';', (string)$model->origin) as $origin) {
        $origin = trim($origin);
        if ($origin !== '') {
            $origins[$origin] = $origin;
        }
    }

    // ações já salvas no banco (podem estar qualificadas ou cruas)
    $savedActions = $model->actions ? explode(';', $model->actions) : [];

    $controllerFQCN   = $model->controller;
    $availableActions = [];
    $originMap        = [];

    if (is_string($controllerFQCN) && class_exists($controllerFQCN)) {
        // list => ids crus; origins => id => ControllerShortName
        $res = \croacworks\essentials\controllers\RoleController::collectControllerActions($controllerFQCN, true);
        $availableActions = $res['list'];     // ['index','create',...]
        $originMap        = $res['origins'];  // ['index'=>'AuthorizationController', ...]
    }

    // Normaliza: se alguma action salva vier qualificada, mantém só a parte após "\"
    if ($savedActions) {
        $savedActions = array_map(static function ($a) {
            $a = trim((string)$a);
            if ($a === '') return '';
            $pos = strrpos($a, '\\');
            return $pos !== false ? substr($a, $pos + 1) : $a;
        }, $savedActions);
        $savedActions = array_values(array_filter($savedActions, static fn($v) => $v !== ''));
    }

    // separa listas
    $fromActions = array_values(array_diff($availableActions, $savedActions));
    $toActions   = $savedActions;
}

// helper para rotular como "ControllerShortName\action"
$labelOf = function (string $id) use ($originMap): string {
    $decl = $originMap[$id] ?? '';
    return ($decl !== '' ? $decl . '\\' : '') . $id;
};

$js = <<<JS
$(function () {
  // plugins
  $('#multiselect').multiselect();
  $('#role-controller').select2({width:'100%',allowClear:true,placeholder:'-- Select one Controller --'});
  $('#role-origin').select2({width:'100%',allowClear:true,placeholder:'',multiple:true});

  // enter para adicionar origin
  $('#add_origin').on('keyup', function(e){
    if (e.keyCode === 13) {
      var v = $('#add_origin').val();
      if (!v) return;
      var opt = new Option(v, v, true, true);
      $('#role-origin').append(opt).trigger('change');
      $('#add_origin').val('');
    }
  });

  function resolveFQCN(selectEl){
    var optEl = $(selectEl).find('option:selected');
    var v = optEl.data('fqcn') || optEl.val();
    if (!v || v === 'index' || /^\\d+$/.test(v)) {
      v = (optEl.text() || '').trim();
    }
    return v;
  }

  $('#role-controller').on('change', function () {
    // limpa listas
    $('#multiselect').empty();
    $('#multiselect_to').empty();

    var fqcn = resolveFQCN(this);
    if (!fqcn) return;

    if (window.Swal) {
      Swal.fire({
        title: 'Carregando...',
        text: 'Listando actions do controller',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => { Swal.showLoading(); }
      });
    }

    $.post('{$actionUrl}', { controller: fqcn }, function(res) {
      if (res && res.success) {
        // res.actions => ids crus: ['index','create',...]
        // res.origins => mapa id => ControllerShortName
        var html = '';
        (res.actions || []).forEach(function(id){
          var decl = (res.origins && res.origins[id]) ? res.origins[id] : '';
          var label = (decl ? decl + '\\\\' : '') + id; // duplica a barra no JS
          html += '<option value="'+id+'" data-decl="'+decl+'" title="'+label+'">'+label+'</option>';
        });
        document.getElementById('multiselect').innerHTML = html;

        if (window.Swal) { Swal.close(); }
      } else {
        if (window.Swal) {
          Swal.close();
          Swal.fire({icon:'error', title:'Ops', text: (res && res.message) || 'Não foi possível carregar as actions'});
        } else {
          alert('Erro ao carregar actions');
        }
      }
    }, 'json').fail(function(xhr){
      if (window.Swal) {
        Swal.close();
        Swal.fire({icon:'error', title:'Erro', text: xhr.responseText || 'Falha na requisição'});
      } else {
        alert('Falha na requisição');
      }
    });
  });

  // se já houver um valor selecionado (edição), dispara para popular as actions
  if ($('#role-controller').val()) {
    $('#role-controller').trigger('change');
  }
});
JS;

$this->registerJs($js, $this::POS_END);

?>

<div class="role-form">

  <?php $form = ActiveForm::begin(); ?>

  <?= $form->field($model, 'group_id')->dropDownList(
        yii\helpers\ArrayHelper::map(Group::find()->asArray()->all(), 'id', 'name'),
        ['prompt' => '-- selecione um grupo --']
  ) ?>

  <?= $form->field($model, 'user_id')->dropDownList(
        yii\helpers\ArrayHelper::map(User::find()->select('id,username')->asArray()->all(), 'id', 'username'),
        ['prompt' => '-- selecione um usuario --']
  ) ?>

  <?= $form->field($model, 'controller')->dropDownList($controllers, [
      'multiple' => false,
      'prompt'   => '-- CONTROLLER --',
  ]) ?>

  <div id="actions" class="form-group">
    <?= Html::label(Yii::t('app', 'Enter origin and digit enter:'), 'add_origin') ?>
    <?= Html::textInput('add_origin', '', ['id' => 'add_origin', 'class' => 'form-control']) ?>
  </div>

  <?= $form->field($model, 'origin[]', ['enableClientValidation' => false])
          ->dropDownList($origins)
          ->label('Origins'); ?>

  <div id="actions" class="form-group">
    <div class="row">
      <div class="col-md-5">
        <select name="from[]" id="multiselect" class="form-control" size="8" multiple="multiple">
          <?php foreach ($fromActions as $action): ?>
            <option value="<?= Html::encode($action) ?>"
                    data-decl="<?= Html::encode($originMap[$action] ?? '') ?>"
                    title="<?= Html::encode($labelOf($action)) ?>">
              <?= Html::encode($labelOf($action)) ?>
            </option>
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

  <?= $form->field($model, 'status')->checkbox() ?>

  <div class="form-group mb-3 mt-3">
    <?= Html::submitButton('<i class="fas fa-save mr-2"></i>' . Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
  </div>

  <?php ActiveForm::end(); ?>

</div>