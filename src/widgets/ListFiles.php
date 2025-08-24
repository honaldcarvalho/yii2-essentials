<?php

namespace croacworks\essentials\widgets;

use Yii;
use yii\web\View;
use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\helpers\Url;

use croacworks\essentials\components\gridview\ActionColumnCustom;
use croacworks\essentials\components\gridview\ResponsiveGridView;
use croacworks\essentials\controllers\AuthorizationController;

class ListFiles extends \yii\bootstrap5\Widget
{
  public $dataProvider;
  public $title;

public function init(): void
  {
    if(empty($this->title)){
        $this->title = Yii::t('app', 'List Files');
    }
  }

  /**$
   * {@inheritdoc}
   */
  public function run()
  {

    $token =  AuthorizationController::User()->access_token;

    $script = <<< JS
    
        $(document).on('click', '.copy-url-btn', function () {
            const url = $(this).data('url');
            navigator.clipboard.writeText(url).then(function() {
                toastr.success("URL copied to clipboard!");
            }, function(err) {
                toastr.error("Failed to copy URL.");
            });
        });

        function removeFiles(e) {
    
            let el = $(e);
            let ids = $('#grid-files').yiiGridView('getSelectedRows');
    
            if (confirm('You really can remove this file(s)?')) {
    
                let keys = [];
                keys = keys.concat(ids);
    
                if(el.attr('id') !== 'remove-files'){
                    keys.push(el.data('id'));
                }
    
                if(keys.length <= 0){
                    alert("No files selected!");
                    return false;
                }
    
                let old_class = el.children("i").attr('class');
                el.prop('disabled',true);
                object = el.children("i");
                object.removeClass(old_class);
                object.addClass('fas fa-sync fa-spin');
    
                $('#overlay-files').show();
    
                $.ajax({
                    type: "POST",
                    url: "/rest/storage/remove-files",
                    data: {keys:keys},
                    headers: {
                        'Authorization': `Bearer {$token}`
                    },
                }).done(function(response) {     
                    
                    if(response.length > 0){
                        $.each(response, function (indexInArray, valueOfElement) { 
                            if(valueOfElement.success){
                                toastr.success(valueOfElement.message);
                            }else{
                                toastr.error(valueOfElement.message);
                            }
                        });
                    }
                    $.pjax.reload({container: "#list-files-grid", async: false});
                    return false;
                }).fail(function (response) {
                    toastr.error("Error on remove files!");
                }).always(function (response) {
                    el.prop('disabled',false);
                    object.removeClass('fas fa-sync fa-spin');
                    object.attr('class',old_class);
                });
    
            }
            return false;
        }
    
        $(function(){
    
            $(document).on('pjax:start', function() {
                $('#overlay-files').show();
            });
            $(document).on('pjax:complete', function() {
                $('#overlay-files').hide();
            });
    
        });
    JS;

    $css = <<< CSS
    CSS;

    \Yii::$app->view->registerCss($css);
    \Yii::$app->view->registerJs($script,View::POS_END);

    $button = Html::button(
                '<i class="fas fa-trash mr-2"></i>' . \Yii::t('app', 'Remove Files'),
                [
                    'onclick' => 'removeFiles(this)',
                    'class' => 'btn btn-danger',
                    'id' => 'remove-files',
                    "data-toggle" => "tooltip",
                    "data-placement" => "top",
                    "title" => \Yii::t('app', 'Remove Files')
                ]
            );

    $gridView = ResponsiveGridView::widget([
                        'id' => 'grid-files',
                        'dataProvider' =>  $this->dataProvider,
                        'columns' => [
                            [
                                'class' => 'yii\grid\CheckboxColumn',
                                // you may configure additional properties here
                            ],
                            [
                                'headerOptions' => ['style' => 'width:4%'],
                                'attribute' => 'folder_id',
                                'format' => 'raw',
                                'value' => function ($data) {
                                    if ($data->folder_id != null)
                                        return Html::a($data->folder->name, Url::toRoute([Yii::getAlias('@web/folder/view'), 'id' => $data->folder_id]));
                                }
                            ],
                            [
                                'headerOptions' => ['style' => 'width:20%'],
                                'attribute' => 'description',
                                'label' => Yii::t('app', 'Description'),
                            ],
                            'type:text:'.Yii::t('app', 'Type'),
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
                            [
                                'headerOptions' => ['style' => 'width:10%'],
                                'attribute' => 'extension',
                                'label' => Yii::t('app', 'Extension'),
                            ],
                            [
                                'headerOptions' => ['style' => 'width:10%'],
                                'attribute' => 'size',
                                'label' => Yii::t('app', 'Size'),
                                'format' => 'bytes',
                                'label' => Yii::t('app', 'Size'),
                            ],
                            [
                                'headerOptions' => ['style' => 'width:10%'],
                                'attribute' => 'duration',
                                'format' => 'duration',
                                'label' => Yii::t('app', 'Duration'),
                            ],
                            [
                                'headerOptions' => ['style' => 'width:25%'],
                                'attribute' => 'created_at',
                                'format' => 'date',
                                'label' => Yii::t('app', 'Created At'),
                            ],
                            [
                                'class'=> ActionColumnCustom::class,
                                'headerOptions' => ['style' => 'width:10%'],
                                'template' => '{copy}{view}{remove}{delete}',
                                'path' => 'app',
                                'controller' => 'file',
                                'buttons' => [
                                    'remove' => function ($url, $model, $key) {
                                        return AuthorizationController::verAuthorization('file', 'remove-file', $model) ?
                                            Html::a(
                                                '<i class="fas fa-unlink"></i>',
                                                yii\helpers\Url::to(['file/remove-file', 'id' => $model->id, 'folder' => $model->folder_id]),
                                                ['class' => 'btn btn-outline-secondary', "data-toggle" => "tooltip", "data-placement" => "top", "title" => \Yii::t('app', 'Remove from folder')]
                                            ) : '';
                                    },
                                    'delete' => function ($url, $model, $key) {
                                        return
                                            Html::button(
                                                '<i class="fas fa-trash"></i>',
                                                ['onclick' => 'removeFiles(this)', 'class' => 'btn btn-outline-secondary', "data-id" => $model->id, "data-toggle" => "tooltip", "data-placement" => "top", "title" => \Yii::t('app', 'Remove')]
                                            );
                                    },
                                    'copy' => function ($url, $model, $key) {
                                        return Html::button(
                                            '<i class="fas fa-copy"></i>',
                                            [
                                                'class' => 'btn btn-outline-secondary copy-url-btn',
                                                'data-url' => Yii::getAlias('@host') . $model->url,
                                                'data-toggle' => 'tooltip',
                                                'data-placement' => 'top',
                                                'title' => Yii::t('app', 'Copy URL')
                                            ]
                                        );
                                    },
                                ]
                            ],
                        ],
                    ]);

    $head = <<< HTML
      <div class="card" id="list-files">

          <div class="card-header">
              <h3 class="card-title">{$this->title}</h3>
          </div>

          <div class="card-body">
              <p>
                $button
              </p>
              <div class="row">
                  <div class="col-md-12">

                      <div id='overlay-files' class='overlay' style='display:none;height: 100%;position: absolute;width: 100%;z-index: 3000;top: 0;left: 0;background: #0000004f;'>
                          <div class='d-flex align-items-center'>
                              <strong> <?= Yii::t('app', 'Loading...') ?></strong>
                              <div class='spinner-border ms-auto' role='status' aria-hidden='true'></div>
                          </div>
                      </div>

    HTML;

    $footer = <<< HTML
                  </div>
                  <!--.col-md-12-->
              </div>
              <!--.row-->
          </div>

      </div>
    HTML;

    echo $head;
    Pjax::begin(['id' => 'list-files-grid']);
      echo $gridView;
    Pjax::end();
    echo $footer;
 
  }
}