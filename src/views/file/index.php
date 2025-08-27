<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\Pjax;
use croacworks\essentials\models\Folder;

$this->title = Yii::t('app', 'Files');
$this->params['breadcrumbs'][] = $this->title;

$deleteUrl = Url::to(['/file/delete-files']);
$moveUrl   = Url::to(['/file/move']); // sua action de mover
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;
  
$this->registerJs(<<<JS
(function bootFilesGrid(){
  // reexecuta após pjax
  document.addEventListener('pjax:end', bootFilesGrid, { once: true });

  // evita dupla inicialização
  if (window.__filesGridInit) return;
  window.__filesGridInit = true;

  // Fancybox inicial (fora do PJAX)
  if (window.Fancybox) Fancybox.bind('[data-fancybox]');

  // Rebind Fancybox após cada reload PJAX
  $(document).on('pjax:end', function(){
    if (window.Fancybox) Fancybox.bind('[data-fancybox]');
  });

  // Helper para pegar IDs selecionados
  function getSelectedIds(){
    // Você definiu name = file_selected[] no CheckboxColumn
    return $('input[name="file_selected[]"]:checked').map(function(){ return $(this).val(); }).get();
  }
  function pjaxReload(){ $.pjax.reload({container:'#grid-pjax', async:false}); }

  // Opcional: CSRF default para todos os POSTs
  $.ajaxSetup({ headers: { 'X-CSRF-Token': '$csrfToken' } });

  // -------- Mover --------
  $(document).on('click','#move-folder', async function(e){
    e.preventDefault();
    const ids = getSelectedIds();
    if (!ids.length) { Swal.fire('Atenção','Nenhum arquivo selecionado.','info'); return; }
    const folderId = $('#folder_id').val();
    if (!folderId) { Swal.fire('Atenção','Selecione uma pasta destino.','info'); return; }

    const ok = await Swal.fire({
      title: 'Confirmar',
      text: `Mover \${ids.length} arquivo(s) para a pasta selecionada?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sim, mover',
      cancelButtonText: 'Cancelar'
    });
    if (!ok.isConfirmed) return;

    $.post('$moveUrl', {
      'file_selected[]': ids,
      folder_id: folderId,
      '$csrfParam': '$csrfToken'
    }).done(function(res){
      if (res && res.success) {
        Swal.fire('OK','Arquivos movidos.','success'); pjaxReload();
      } else {
        Swal.fire('Erro', (res && res.error) || 'Falha.','error');
      }
    }).fail(function(xhr){
      const msg = xhr.responseJSON?.error || xhr.statusText || 'Falha.';
      Swal.fire('Erro', msg, 'error');
    });
  });

  // -------- Delete múltiplo --------
  $(document).on('click','#delete-files', async function(e){
    e.preventDefault();
    const ids = getSelectedIds();
    if (!ids.length) { Swal.fire('Atenção','Nenhum arquivo selecionado.','info'); return; }

    const ok = await Swal.fire({
      title: 'Confirmar',
      text: `Excluir \${ids.length} arquivo(s)? Esta ação não pode ser desfeita.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sim, excluir',
      cancelButtonText: 'Cancelar'
    });
    if (!ok.isConfirmed) return;

    $.post('$deleteUrl', {
      'file_selected[]': ids,
      '$csrfParam': '$csrfToken'
    }).done(function(res){
      if (res && res.success){
        Swal.fire('OK','Arquivos excluídos.','success'); pjaxReload();
      } else {
        Swal.fire('Erro', (res && res.error) || 'Falha.','error');
      }
    }).fail(function(xhr){
      const msg = xhr.responseJSON?.error || xhr.statusText || 'Falha.';
      Swal.fire('Erro', msg, 'error');
    });
  });

  // -------- Delete individual --------
  $(document).on('click','[data-action="file-delete"]', async function(e){
    e.preventDefault();
    e.stopImmediatePropagation();
    const url = $(this).attr('href') || $(this).data('url');
    const ok = await Swal.fire({
      title: 'Confirmar',
      text: 'Excluir este arquivo?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sim, excluir',
      cancelButtonText: 'Cancelar'
    });
    if (!ok.isConfirmed) return;

    $.post(url, { '$csrfParam': '$csrfToken' })
      .done(function(res){
        if (res && res.success){
          Swal.fire('OK','Arquivo excluído.','success'); pjaxReload();
        } else {
          Swal.fire('Atenção', (res && res.error) || 'Falha.','warning');
        }
      })
      .fail(function(xhr){
        const msg = xhr.responseJSON?.error || xhr.statusText || 'Falha.';
        Swal.fire('Erro', msg, 'error');
      });
  });
})();
JS, View::POS_END);

// Botão extra (apenas render, ação é AJAX acima)
$delete_files_button[] =
[
    'controller'=>'file',
    'action'=>'delete-files',
    'icon'=>'<i class="fas fa-trash"></i>',
    'text'=>'Delete File(s)',
    'link'=>'javascript:;',
    'options'=>[
        'id' => 'delete-files',
        'class' => 'btn btn-danger btn-block-m',
    ],
];

?>
<div class="container-fluid">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-body">

          <div class="row mb-2">
            <div class="col">
              <?= croacworks\essentials\widgets\DefaultButtons::widget([
                  'controller' => 'File',
                  'show' => [],
                  'extras'=> $delete_files_button
              ]) ?>
            </div>

            <div class="input-group col">
              <?= Html::button(
                  '<i class="fas fa-exchange-alt mr-2"></i>' . Yii::t('app', 'Move to:'),
                  ['class'=>'btn input-group-text btn-success', 'id'=>'move-folder']
              ) ?>
              <?= Html::dropDownList(
                  'folder_id',
                  null,
                  yii\helpers\ArrayHelper::map(Folder::find()->asArray()->all(), 'id', 'name'),
                  ['id'=>'folder_id','class'=>'form-control']
              ) ?>
            </div>
          </div>

          <?php Pjax::begin([
              'id'=>'grid-pjax',
              'timeout'=>8000,
              'enablePushState'=>false,
              'enableReplaceState'=>false,
          ]); ?>

          <?= GridView::widget([
              'dataProvider' => $dataProvider,
              'filterModel'  => $searchModel,
              'tableOptions' => ['class'=>'table table-striped table-bordered'],
              'columns' => [
                  ['class' => 'yii\grid\CheckboxColumn', // <-- resolve seleção múltipla
                   'checkboxOptions' => function($model){
                       return ['value'=>$model->id, 'class'=>'file-item'];
                   },
                   'name' => 'file_selected[]', // nome certo para coletar via jQuery
                  ],
                  'id',
                  'name',
                  'folder.name:text:Folder',
                  [
                      'attribute'=>'folder_id',
                      'format'=>'raw',
                      'value'=> function($data){
                          if ($data->folder_id != null) {
                              return Html::a(
                                  $data->folder->name,
                                  Url::to(['/folder/view', 'id' => $data->folder_id]),
                                  ['data-pjax'=>0] // abre fora do pjax
                              );
                          }
                          return null;
                      }
                  ],
                  'description',
                  'type',
                  [
                      'headerOptions' => ['style' => 'width:10%'],
                      'header' => 'Preview',
                      'format' => 'raw',
                      'value' => function ($data) {
                          $url = $data->url;
                          $type = '';
                          if ($data->type == 'doc') {
                              if ($data->extension != 'pdf') {
                                  $url = 'https://docs.google.com/viewer?url=' . Yii::getAlias('@host') . $data->url;
                              }
                              $type = 'iframe';
                          }
                          return Html::a(
                              "<img class='brand-image img-circle elevation-3' width='50' src='{$data->urlThumb}' />",
                              $url,
                              [
                                  'class' => 'btn btn-outline-secondary',
                                  'data-fancybox' => '',
                                  'data-type' => $type,
                                  'title' => Yii::t('app', 'View'),
                                  'data-pjax'=>0, // evita interceptação do PJAX
                              ]
                          );
                      }
                  ],
                  'extension',
                  'size:bytes',
                  'duration:time',
                  [
                      'attribute' => 'created_at',
                      'format' => ['date', 'php:Y-m-d'],
                      'label' => Yii::t('app', 'Created At'),
                      'filter' => Html::input('date', ucfirst(Yii::$app->controller->id).'Search[created_at]', $searchModel->created_at, ['class'=>'form-control'])
                  ],
                  [
                      'attribute' => 'updated_at',
                      'format' => ['date', 'php:Y-m-d'],
                      'label' => Yii::t('app', 'Updated At'),
                      'filter' => Html::input('date', ucfirst(Yii::$app->controller->id).'Search[updated_at]', $searchModel->updated_at, ['class'=>'form-control'])
                  ],
                  [
                      'class' => \croacworks\essentials\components\gridview\ActionColumnCustom::class,
                      'contentOptions' => ['style'=>'white-space:nowrap;'],
                      'template' => '{view} {update} {ajax-delete}',

                      'buttons' => [
                          'ajax-delete' => function ($url, $model) {
                              $url = \yii\helpers\Url::to(['/file/delete', 'id' => $model->id]);
                              return \yii\helpers\Html::a('<i class="fas fa-trash"></i>', $url, [
                                  'title'        => Yii::t('app', 'Delete'),
                                  'class'        => 'btn btn-outline-danger',
                                  'data-action'  => 'file-delete',
                                  'data-pjax'    => 0,            
                                  'data-method'  => false,        
                                  'data-confirm' => false,
                              ]);
                          },
                      ],
                  ],
              ],
          ]); ?>

          <?php Pjax::end(); ?>

        </div>
      </div>
    </div>
  </div>
</div>
