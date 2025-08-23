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

$script = <<<JS
// -------- Helpers ----------
function getSelectedIds() {
  const ids = [];
  $('input[name="file_selected[]"]:checked').each(function(){
    ids.push($(this).val());
  });
  return ids;
}
function pjaxReload(){ $.pjax.reload({container:'#grid-pjax', async:false}); }

// Rebinda Fancybox após render inicial e toda vez que o PJAX terminar
function bindEnhancements(){
  if (window.Fancybox) Fancybox.bind("[data-fancybox]");
}
bindEnhancements();
$(document).on('pjax:end', bindEnhancements);

// -------- Move (AJAX) ----------
$(document).on('click', '#move-folder', async function(e){
  e.preventDefault();
  const ids = getSelectedIds();
  if (!ids.length) { 
    Swal.fire('Atenção', 'Nenhum arquivo selecionado.', 'info');
    return false; 
  }
  const folderId = $('#folder_id').val();
  if (!folderId) {
    Swal.fire('Atenção', 'Selecione uma pasta destino.', 'info');
    return false;
  }
  // Confirmação
  const ok = await Swal.fire({
    title: 'Confirmar',
    text: `Mover \${ids.length} arquivo(s) para a pasta selecionada?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Sim, mover',
    cancelButtonText: 'Cancelar'
  });
  if (!ok.isConfirmed) return false;

  $.ajax({
    url: '$moveUrl',
    type: 'POST',
    dataType: 'json',
    data: {
      'file_selected[]': ids,
      folder_id: folderId,
      '$csrfParam': '$csrfToken'
    }
  }).done(function(res){
    if (res && res.success) {
      Swal.fire('OK', 'Arquivos movidos com sucesso.', 'success');
      pjaxReload();
    } else {
      Swal.fire('Erro', res?.error || 'Falha ao mover.', 'error');
    }
  }).fail(function(xhr){
    Swal.fire('Erro', xhr.responseJSON?.error || 'Falha na requisição.', 'error');
  });
  return false;
});

// -------- Delete múltiplo (AJAX) ----------
$(document).on('click', '#delete-files', async function(e){
  e.preventDefault();
  const ids = getSelectedIds();
  if (!ids.length) {
    Swal.fire('Atenção', 'Nenhum arquivo selecionado.', 'info');
    return false;
  }

  const ok = await Swal.fire({
    title: 'Confirmar',
    text: `Excluir \${ids.length} arquivo(s)? Esta ação não pode ser desfeita.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sim, excluir',
    cancelButtonText: 'Cancelar'
  });
  if (!ok.isConfirmed) return false;

  $.ajax({
    url: '$deleteUrl',
    method: 'POST',
    dataType: 'json',
    data: { 'file_selected[]': ids, '$csrfParam': '$csrfToken' }
  }).done(function(res){
    if (!res || res.success !== true) {
      Swal.fire('Erro', (res && res.error) ? res.error : 'Falha desconhecida.', 'error');
      return;
    }
    let html = `
      <div style="text-align:left">
        <p><b>Deletados:</b> \${res.summary.deleted}</p>
        <p><b>Bloqueados (em uso):</b> \${res.summary.blocked}</p>
        <p><b>Falhas:</b> \${res.summary.failed}</p>
    `;
    if (res.blocked?.length) {
      html += `<hr><b>Detalhes bloqueados:</b><ul>`;
      res.blocked.forEach(b=>{
        const refs = (b.refs||[]).map(r=>`\${r.table}.\${r.column}`).join(', ');
        html += `<li>#\${b.id}: \${refs}</li>`;
      });
      html += `</ul>`;
    }
    if (res.failed?.length) {
      html += `<hr><b>Falhas:</b><ul>`;
      res.failed.forEach(f=>{
        html += `<li>#\${f.id}: \${f.error||'erro'}</li>`;
      });
      html += `</ul>`;
    }
    html += `</div>`;

    Swal.fire({ title: 'Resultado', html, icon: 'info' });
    pjaxReload();
  }).fail(function(xhr){
    Swal.fire('Erro', xhr.responseJSON?.error || 'Falha na requisição.', 'error');
  });

  return false;
});

// -------- Delete individual (AJAX) opcional --------
$(document).on('click', '[data-action="file-delete"]', async function(e){
  e.preventDefault();
  const url = $(this).attr('href') || $(this).data('url');
  const ok = await Swal.fire({
    title: 'Confirmar',
    text: 'Excluir este arquivo?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sim, excluir',
    cancelButtonText: 'Cancelar'
  });
  if (!ok.isConfirmed) return false;

  $.post(url, {'$csrfParam': '$csrfToken'})
    .done(function(res){
      if (res?.success) {
        Swal.fire('OK', 'Arquivo excluído.', 'success');
        pjaxReload();
      } else {
        const msg = res?.blocked ? 'Arquivo em uso, não pode ser removido.' : (res?.result?.message || res?.error || 'Falha.');
        Swal.fire('Atenção', msg, 'warning');
      }
    })
    .fail(function(){
      Swal.fire('Erro', 'Falha na requisição.', 'error');
    });
});
JS;

$this->registerJs($script, View::POS_END);

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
                  'show' => ['create'],
                  'buttons_name' => ['create' => 'Create File'],
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
                  ],
              ],
          ]); ?>

          <?php Pjax::end(); ?>

        </div>
      </div>
    </div>
  </div>
</div>
