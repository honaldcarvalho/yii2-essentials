<?php

use croacworks\essentials\controllers\Controller as ControllersController;
use yii\helpers\Html;
use yii\grid\GridView;
use croacworks\essentials\components\gridview\ActionColumn;
use croacworks\essentials\controllers\AuthController;
use croacworks\essentials\models\Folder;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel croacworks\essentials\models\FileSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Files');
$this->params['breadcrumbs'][] = $this->title;

$script = <<< JS
    $(function(){
        Fancybox.bind("[data-fancybox]");
    });
    $('#move-folder').click(function(e){
        var items = $('.file-item:checked');
        if(items.length > 0){     

            var form = document.createElement('form');

            form.setAttribute('action','/file/move');
            form.setAttribute('method','post');
            form.setAttribute('id','form-move');
            document.body.appendChild(form);

            let clone = $('#folder_id').clone().val( $('#folder_id').val());

            clone.appendTo('#form-move');

            $('.file-item:checked').each(function(i){
                $(this).clone().appendTo('#form-move');
            });
            form.submit(); 
        }

        return false;
    });

// Requer SweetAlert2 já carregado na página
(function(){
  const csrf = $('meta[name="csrf-token"]').attr('content');

  function getSelectedIds() {
    const ids = [];
    $('.file-item:checked').each(function(){ ids.push($(this).val()); });
    return ids;
  }

  // Delete múltiplo (AJAX)
  $('#delete-files').off('click').on('click', async function(e){
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
      url: '/file/delete-files',
      method: 'POST',
      dataType: 'json',
      data: { 'file_selected': ids, _csrf: csrf }
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

      if (res.blocked && res.blocked.length) {
        html += `<hr><b>Detalhes bloqueados:</b><ul>`;
        res.blocked.forEach(b=>{
          const refs = (b.refs||[]).map(r=>`\${r.table}.\${r.column}`).join(', ');
          html += `<li>#\${b.id}: \${refs}</li>`;
        });
        html += `</ul>`;
      }

      if (res.failed && res.failed.length) {
        html += `<hr><b>Falhas:</b><ul>`;
        res.failed.forEach(f=>{
          html += `<li>#\${f.id}: \${f.error||'erro'}</li>`;
        });
        html += `</ul>`;
      }

      html += `</div>`;

      Swal.fire({
        title: 'Resultado',
        html: html,
        icon: 'info'
      });

      // Recarrega a grid
      if ($.support.pjax && $('#grid-pjax').length) {
        $.pjax.reload({container:'#grid-pjax', async:false});
      } else {
        // fallback
        location.reload();
      }
    }).fail(function(xhr){
      Swal.fire('Erro', xhr.responseJSON?.error || 'Falha na requisição.', 'error');
    });

    return false;
  });

  // (Opcional) Delete individual via AJAX: data-action="/file/delete?id=123"
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

    $.post(url, {_csrf: csrf})
      .done(function(res){
        if (res?.success) {
          Swal.fire('OK', 'Arquivo excluído.', 'success');
          if ($.support.pjax && $('#grid-pjax').length) {
            $.pjax.reload({container:'#grid-pjax', async:false});
          } else {
            location.reload();
          }
        } else {
          const msg = res?.blocked ? 'Arquivo em uso, não pode ser removido.' : (res?.result?.message || res?.error || 'Falha.');
          Swal.fire('Atenção', msg, 'warning');
        }
      })
      .fail(function(){
        Swal.fire('Erro', 'Falha na requisição.', 'error');
      });
  });

})();

JS;

$this->registerJs($script, View::POS_END);

$delete_files_button[] = 
[
    'controller'=>'file',
    'action'=>'delete-files',
    'icon'=>'<i class="fas fa-trash"></i>',
    'text'=>'Delete File(s)',
    'link'=>'javascript:;',
    'options'=>                    [
        'id' => 'delete-files',
        'class' => 'btn btn-danger btn-block-m',
        'data' => [
            'confirm' => Yii::t('app', 'Are you sure you want to delete this item(s)?'),
            'method' => 'get'
        ],
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

                            <?= croacworks\essentials\widgets\DefaultButtons::widget(
                                [
                                    'controller' => 'File',
                                    'show' => ['create'],
                                    'buttons_name' => ['create' => 'Create File'],
                                    'extras'=>  $delete_files_button
                                ]
                            )
                            ?>
                        </div>

                        <div class="input-group col">
                            <?= Html::button('<i class="fas fa-exchange-alt mr-2"></i>' . Yii::t('app', 'Move to:'), ['class' => 'btn input-group-text btn-success', 'id' => 'move-folder']) ?>                            
                            <?= Html::dropDownList('folder_id', null, yii\helpers\ArrayHelper::map(Folder::find()->asArray()->all(), 'id', 'name'), ['id'=>'folder_id','class' => 'form-control']); ?>
                        </div>
                    </div>

                    <?php // echo $this->render('_search', ['model' => $searchModel]); 
                    Pjax::begin(['id'=>'grid-pjax', 'timeout'=>8000]);

                    ?>

                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        'columns' => [
                            'id',
                            'name',
                            [
                                'header' => '',
                                'format' => 'raw',
                                'value' => function ($data) {
                                    return Html::checkbox('file_selected[]', false, ['value' => $data->id, 'class' => 'file-item']);
                                }
                            ],
                            'folder.name:text:Folder',
                            [
                                'attribute'=>'folder_id',
                                'format'=>'raw',
                                'value'=> function($data){
                                    if($data->folder_id != null)
                                        return Html::a($data->folder->name,Url::toRoute([Yii::getAlias('@web/folder/view'), 'id' => $data->folder_id]));
                                }
                            ],
                            'description',
                            //'path',
                            'type',
                            [
                                'headerOptions' => ['style' => 'width:10%'],
                                'header' => 'Preview',
                                'format' => 'raw',
                                'value' => function ($data) {
                                    $url = $data->url;
                                    $type = '';
                                    if($data->type == 'doc'){
                                        if($data->extension != 'pdf'){
                                            $url = 'https://docs.google.com/viewer?url=' .Yii::getAlias('@host') . $data->url;
                                        }
                                        $type = 'iframe';
                                    }
                                    
                                    return Html::a(
                                        "<img class='brand-image img-circle elevation-3' width='50' src='{$data->urlThumb}' />",
                                        $url,
                                        [
                                            'class' => 'btn btn-outline-secondary', 
                                            "data-fancybox" => "", 
                                            "data-type"=>"{$type}", 
                                            "title" => \Yii::t('app', 'View')
                                        ]
                                    );
                                }
                            ],
                            'extension',
                            'size:bytes',
                            'duration:time',
                            [
                                'attribute' => 'created_at',
                                'format' => 'date',
                                'label' => Yii::t('app', 'Created At'),
                                'filter' => Html::input('date', ucfirst(Yii::$app->controller->id) . 'Search[created_at]', $searchModel->created_at, ['class' => 'form-control dateandtime'])
                            ],
                            [
                                'attribute' => 'updated_at',
                                'format' => 'date',
                                'label' => Yii::t('app', 'Updated At'),
                                'filter' => Html::input('date', ucfirst(Yii::$app->controller->id) . 'Search[updated_at]', $searchModel->updated_at, ['class' => 'form-control dateandtime'])
                            ],

                            [
                                'class'=>croacworks\essentials\components\gridview\ActionColumnCustom::class,
                            ],
                        ],
                    ]); 
                    Pjax::end();
                    ?>

                </div>
                <!--.card-body-->
            </div>
            <!--.card-->
        </div>
        <!--.col-md-12-->
    </div>
    <!--.row-->
</div>