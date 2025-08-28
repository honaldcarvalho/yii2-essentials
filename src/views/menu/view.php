<?php

use croacworks\essentials\components\gridview\ActionColumnCustom;
use croacworks\essentials\controllers\CommonController;
use croacworks\essentials\controllers\RoleController;
use croacworks\essentials\widgets\AppendModel;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\SysMenu */

$this->title = $model->label;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Menus'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

\yii\web\YiiAsset::register($this);
$script = <<< JS
    jQuery("#grid-menu .table tbody").sortable({
        update: function(event, ui) {
            const tbody = $(this); // <- só esse tbody
            let items  = [];

            $('#overlay').show();

            tbody.find("tr").each(function () {
                items.push($(this).attr("data-key"));
            });

            $.ajax({
            method: "POST",
            url: "/menu/order",
            data: {
                items: items,
                modelClass: "croacworks\\essentials\\models\\SysMenu",
                field: "order"
            }
            }).done(function() {
                toastr.success("Ordem atualizada");
            }).fail(function() {
                toastr.error("Erro ao atualizar a ordem. Recarregue a página");
            }).always(function(){
                $('#overlay').hide();
            });
        }
    });
  $('#submit-auto-add').on('click', function() {
    const controller = $('#controller').val().trim();
    const action = $('#action').val().trim() || 'index';

    if (!controller) {
        toastr.error('Informe o controller.');
        return;
    }

    $('#submit-auto-add').prop('disabled', true);

    $.ajax({
        url: '/menu/auto-add',
        method: 'GET',
        data: { controller, action },
        success: function(response) {
            location.reload();
        },
        error: function(xhr) {
            const msg = xhr.responseText || 'Erro ao adicionar menu.';
            toastr.error(msg);
        },
        complete: function() {
            $('#submit-auto-add').prop('disabled', false);
            $('#modal-auto-add').modal('hide');
        }
    });
});

JS;
$controllers = RoleController::getAllControllers(); // FQCNs

$assetsDir   = CommonController::getAssetsDir();
$controllers = RoleController::getAllControllers(); // FQCNs
$actionUrl   = Url::to(['/role/get-actions']);      // AJAX p/ listar actions por controller

$appendJs = <<<JS
(function(){
  const modal      = $('#save-menu');          // id do modal gerado pelo AppendModel (uniqueId = 'menu')
  const $ctrl      = $('#menu-controller');
  const $act       = $('#menu-action');
  const $icon      = $('#menu-icon');
  const $iconStyle = $('#menu-icon_style');
  const $visible   = $('#menu-visible');
  const $url       = $('#menu-url');
  const $path      = $('#menu-path');
  const $active    = $('#menu-active');

  function controllerBaseName(fqcn){
    if(!fqcn) return '';
    const parts = fqcn.split('\\\\');
    return (parts.pop() || '').replace(/Controller$/,''); // ex: FormResponse
  }
  function controllerIdFromFQCN(fqcn){
    const base = controllerBaseName(fqcn);
    return base.replace(/([a-z0-9])([A-Z])/g,'$1-$2').toLowerCase(); // ex: form-response
  }
  function namespaceRoot(fqcn){
    return (fqcn && fqcn.split('\\\\')[0] ? fqcn.split('\\\\')[0] : 'app').toLowerCase();
  }

  function refreshVisible(){
    const c = $ctrl.val() || '';
    const a = $act.val()  || '';
    if(c && a){ $visible.val(c + ';' + a); }
  }
  function refreshUrlActivePath(){
    const fqcn   = $ctrl.val();
    if(!fqcn) return;
    const ctrlId = controllerIdFromFQCN(fqcn);
    const ns     = namespaceRoot(fqcn);
    const act    = $act.val() || 'index';
    $active.val(ctrlId);
    $path.val(ns);
    $url.val('/' + ctrlId + '/' + act);
  }

  // Carregar actions ao mudar controller
  $ctrl.on('change', function(){
    const fqcn = $(this).val();
    $act.html('<option></option>').val(null).trigger('change');
    if(fqcn){
      $.post('{$actionUrl}', { controller: fqcn }, function(res){
        if(res && res.success){
          let opts = '<option></option>';
          res.actions.forEach(a => opts += `<option value="\${a}">\${a}</option>`);
          $act.html(opts).trigger('change');
        }
      }, 'json');
    }
    refreshUrlActivePath();
    refreshVisible();
  });

  // Atualiza campos derivados quando a action muda
  $act.on('change', function(){
    refreshUrlActivePath();
    refreshVisible();
  });

  // Carregar lista de ícones FA uma vez
  async function ensureIcons(){
    if($icon.data('loaded')) return;
    try{
      const res   = await fetch('{$assetsDir}/plugins/fontawesome-free/list.json');
      const icons = await res.json();
      let html = '<option></option>';
      icons.forEach(i => { html += `<option value="\${i}" data-icon="\${i}">\${i}</option>`; });
      $icon.html(html).trigger('change');
      // Embeleza resultados do Select2 com o ícone
      $icon.on('select2:open', function(){
        $('#select2-menu-icon-results li').each(function(){
          const txt = $(this).text();
          $(this).html('<i class="'+txt+'"></i> '+txt);
        });
      });
      $icon.data('loaded', true);
    }catch(e){
      console.warn('Falha ao carregar ícones:', e);
    }
  }

  // Ao abrir o modal, garantir Select2 e ícones
  document.addEventListener('shown.bs.modal', function(e){
    if(e.target.id === 'save-menu'){
      ensureIcons();
    }
  });

  // No modo de edição: garantir que as actions do controller atual sejam preenchidas
  window.appendMenuEditHydrate = function(){
    const currentCtrl = $ctrl.val();
    const currentAct  = $act.val();
    if(currentCtrl){
      $.post('{$actionUrl}', { controller: currentCtrl }, function(res){
        if(res && res.success){
          let opts = '<option></option>';
          res.actions.forEach(a => { opts += `<option value="\${a}">\${a}</option>`; });
          $act.html(opts).val(currentAct).trigger('change');
        }
      }, 'json');
    }
    ensureIcons();
  };

  // No "Novo": defaults amigáveis
  window.appendMenuNewDefaults = function(){
    if(!$iconStyle.val()) $iconStyle.val('fas');
  };
})();
JS;

$this->registerJs($appendJs);

$this::registerJs($script, $this::POS_END);

?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <p>
                        <?= croacworks\essentials\widgets\DefaultButtons::widget([
                            'controller' => Yii::$app->controller->id,
                            'model' => $model,
                            'verGroup' => false
                        ]) ?>
                    </p>
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            [
                                'attribute' => 'parent_id',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    if ($model->parent_id != null)
                                        return Html::a($model->parent->label, Url::toRoute([Yii::getAlias('@web/menu/view'), 'id' => $model->parent_id]));
                                }
                            ],
                            [
                                'attribute' => 'label',
                                'label' => 'Menu',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    /** @var \croacworks\essentials\models\SysMenu $model */
                                    $count = $model->getChildren()->count();
                                    $badge = $count ? " <span class='badge bg-secondary'>{$count}</span>" : '';
                                    return \yii\helpers\Html::a(
                                        \yii\helpers\Html::encode($model->label) . $badge,
                                        ['view', 'id' => $model->id]
                                    );
                                }
                            ],
                            'icon',
                            'visible',
                            'url:url',
                            'path',
                            'active',
                            'status:boolean',
                        ],
                    ]) ?>
                </div>
                <!--.col-md-12-->
            </div>
            <!--.row-->
        </div>
        <!--.card-body-->
    </div>
    <!--.card-->

    <?= AppendModel::widget([
        'title' => Yii::t('app', 'Menus'),
        'attactModel' => 'SysMenu',
        'controller' => 'menu',
        'uniqueId' => 'menu',
        'attactClass' => 'croacworks\\essentials\\models\\SysMenu',
        'dataProvider' => new \yii\data\ActiveDataProvider([
            'query' => $model->getChildren(),
        ]),
        'showFields' => [
            [
                'attribute' => 'label',
                'label' => 'Menu',
                'format' => 'raw',
                'value' => function ($model) {
                    /** @var \croacworks\essentials\models\SysMenu $model */
                    $count = $model->getChildren()->count();
                    $badge = $count ? " <span class='badge bg-secondary'>{$count}</span>" : '';
                    return \yii\helpers\Html::a(
                        \yii\helpers\Html::encode($model->label) . $badge,
                        ['view', 'id' => $model->id]
                    );
                }
            ],
            'icon',
            'order',
            'url:url',
            'status:boolean',
        ],
        'fields' => [
            // Sempre vincula ao pai atual
            ['name' => 'parent_id',   'type' => 'hidden', 'value' => $model->id],

            ['name' => 'label',       'type' => 'text'],

            // Controller (FQCN) - Select2 com a lista vinda do PHP
            [
                'name'  => 'controller',
                'type'  => 'select2',
                'value' => $controllers, // array(FQCN => FQCN)
                'before' => '<label class="form-label">Controller (FQCN)</label>',
            ],

            // Action - Select2 (opções serão carregadas via AJAX quando o controller mudar)
            [
                'name'  => 'action',
                'type'  => 'select2',
                'value' => [],
                'before' => '<label class="form-label">Action</label>',
            ],

            // Ícone FontAwesome - Select2 (carregado via JS do list.json)
            [
                'name'  => 'icon',
                'type'  => 'select2',
                'value' => [],
                'before' => '<label class="form-label">Ícone (Font Awesome)</label>',
            ],

            // Demais campos iguais ao seu form
            [
                'name'  => 'icon_style',
                'type'  => 'text',
                'value' => 'fas',
            ],
            ['name' => 'visible', 'type' => 'text'],
            [
                'name'  => 'url',
                'type'  => 'text',
                'value' => '#'
            ],
            [
                'name'  => 'path',
                'type'  => 'text',
                'value' => 'app'
            ],
            ['name' => 'active', 'type' => 'text'],
            ['name' => 'order',  'type' => 'number'],
            ['name' => 'status', 'type' => 'checkbox'],
        ],

        // Hooks para inicialização/edição
        'newCallBack'     => "appendMenuNewDefaults();",
        'editCallBack'    => "appendMenuEditHydrate();",
        'editCallBefore'  => "", // se quiser limpar algo antes de preencher, use aqui
        'callBack'        => "", // após salvar
    ]); ?>
</div>