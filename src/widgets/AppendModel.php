<?php

namespace croacworks\essentials\widgets;

use croacworks\essentials\components\gridview\ActionColumnCustom;
use croacworks\essentials\components\gridview\ResponsiveGridView;
use Yii;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\web\View;
use yii\widgets\Pjax;

/**
 Example:
             <?= AppendModel::widget([
                'uniqueId'=>'InstituicaoAppendId',
                'title'=>'Instituições',
                'attactModel'=>'Instituicao',
                'controller'=>'instituicao',
                'template' => '{status} {view} {remove}',
                'order'=>true,
                'reloadGrid'=>null,
                'orderField'=>'ordem',
                'orderModel'=>'Instituicao',
                'attactClass'=>'app\\models\\Instituicao',
                'dataProvider' => new \yii\data\ActiveDataProvider([
                    'query' => $model->getInstituicoes(),
                ]),
                'showFields'=>[
                    'id',
                    'titulo',
                    'nome',
                    'ordem',
                    'status:boolean'
                ],
                'fields'=>
                [
                    [
                        'name'=>'instituicao_secao_id',
                        'type'=>'hidden',
                        'value'=>$model->id
                    ],
                    [
                        'name'=>'titulo',
                        'type'=>'text'
                    ],
                    [
                        'name'=>'nome',
                        'type'=>'text'
                    ],
                    [
                        'name'=>'status',
                        'type'=>'checkbox'
                    ],
                ]
            ]); ?>
 */
class AppendModel extends \yii\bootstrap5\Widget
{

    public $dataProvider;
    public $title = '';
    public $modalSize = '';
    public $controller = null;
    public $attactClass;
    public $attactModel;
    public $childModel;
    public $callBack = '';
    public $newCallBack = '';
    public $delCallBack = '';
    public $editCallBack = '';
    public $editCallBefore = '';
    public $childField;
    public $path = 'app';
    public $template = '{status}{view}{edit}{remove}';
    public $gridId = null;
    public $fields;
    public $options = [];
    public $showFields;
    public $order = false;
    public $orderField = 'order';
    public $orderModel = null;

    public $removeUrl;
    public $getUrl;
    public $saveUrl;
    public $statusUrl;
    public $random;
    public $action_columns = [];

    /**
     * {@inheritdoc}
     */

    public $uniqueId;

    public function init(): void
    {
        parent::init();
        $style = <<< CSS
            optgroup {
                display:none;
            }

            .fancybox__content {
                padding: 0 !important;
                margin: 0 !important;
            }

            .fancybox__slide::before, .fancybox__slide::after{
                margin:0!important;
            }

            @media (max-width: 768px) {
                .modal.show {
                    display: flex !important;
                    align-items: center;
                    justify-content: center;
                }

                .modal-dialog {
                    margin: auto !important;
                    max-width: 90%;
                }

                .modal-content {
                    max-height: 90vh;
                    overflow-y: auto;
                }

                body.modal-open {
                    overflow: hidden;
                }
            }
        CSS;
        \Yii::$app->view->registerCss($style);
        $style = <<<CSS
            .modal .modal-body {
                max-height: 70vh;
                overflow-y: auto;
            }

            .modal .ace-container {
                min-height: 300px;
            }
        CSS;

        Yii::$app->view->registerCss($style);
        if ($this->uniqueId === null) {
            $this->uniqueId = uniqid($this->controller . '_');
        }
        if ($this->gridId === null) {
            $this->gridId = "#grid-{$this->controller}";
        }
    }

    public function run()
    {
        $columns = [['class' => 'yii\grid\CheckboxColumn']];

        $lower = $this->controller;
        $this->random = rand(10000, 99999);
        $this->removeUrl = "/{$this->controller}/remove-model?modelClass={$this->attactModel}";
        $this->getUrl = "/{$this->controller}/get-model?modelClass={$this->attactModel}";
        $this->saveUrl = "/{$this->controller}/save-model?modelClass={$this->attactModel}";
        $this->statusUrl = "/{$this->controller}/status-model?modelClass={$this->attactModel}";
        $form_name = strtolower($this->attactModel);
        $columns = array_merge($columns, $this->showFields);

        array_push(
            $columns,
            [
                'class' => ActionColumnCustom::class,
                'headerOptions' => ['style' => 'width:10%'],
                'template' => $this->template,
                'path' =>  $this->path,
                'controller' => $this->controller,
                'uniqueId' => "{$this->uniqueId}",
                'order' => $this->order,
                'orderField' => $this->orderField,
                'orderModel' => $this->orderModel,
                'buttons' =>
                array_merge(
                    $this->action_columns,
                    [
                        'status' => function ($url, $model, $key) {
                            return Html::a(
                                '<i class="fas fa-toggle-' . (!$model->status ? 'off' : 'on') . '"></i>',
                                'javascript:;',
                                [
                                    'onclick' => "status{$this->attactModel}(this);",
                                    'data-link' => "{$this->statusUrl}&id=$model->id",
                                    'class' => 'btn btn-outline-secondary status',
                                    "data-toggle" => "tooltip",
                                    "data-placement" => "top",
                                    "title" => \Yii::t('*', "Change Status {$this->attactModel}")
                                ]
                            );
                        },
                        'remove' => function ($url, $model, $key) {
                            return Html::a(
                                '<i class="fas fa-trash"></i>',
                                'javascript:;',
                                [
                                    'onclick' => "remove{$this->attactModel}(this);",
                                    'data-link' => "{$this->removeUrl}&id=$model->id",
                                    'class' => 'btn btn-outline-secondary remove',
                                    "data-toggle" => "tooltip",
                                    "data-placement" => "top",
                                    "title" => \Yii::t('*', "Remove {$this->attactModel}")
                                ]
                            );
                        },
                        'edit' => function ($url, $model, $key) {
                            return Html::a(
                                '<i class="fas fa-pen"></i>',
                                'javascript:;',
                                [
                                    'onclick' => "get{$this->attactModel}(this);",
                                    'data-link' => "{$this->getUrl}&id=$model->id",
                                    'class' => 'btn btn-outline-secondary edit',
                                    "data-toggle" => "tooltip",
                                    "data-placement" => "top",
                                    "title" => \Yii::t('*', "Edit {$this->attactModel}")
                                ]
                            );
                        },
                    ]
                )
            ]
        );

        Yii::$app->view->registerJs(<<<JS

        $(function(){
            let modal_{$this->attactModel} = null;

            modal_{$this->attactModel} = new bootstrap.Modal(document.getElementById('save-{$this->uniqueId}'), {
                keyboard: true
            });
            $('.dropdown-select2').select2({width:'100%',allowClear:true,placeholder:'Selecione',dropdownParent: $('#save-{$this->uniqueId}')});
            
            $(document).on('pjax:start', function() {
                $('#overlay-{$this->uniqueId}').show();
            });
            $(document).on('pjax:complete', function() {
                $('#overlay-{$this->uniqueId}').hide();
            });
            Fancybox.bind("[data-fancybox]");
        });

        onPjaxReady((root) => {

            function clearForms{$this->attactModel}() {
                
                const form = $("#form-{$this->uniqueId}");

                // Reset nativo do form
                form[0].reset();

                // Limpa TODOS os inputs, inclusive hidden
                form.find(':input')
                    .not(':button, :submit, :reset')
                    .val('')
                    .prop('checked', false)
                    .prop('selected', false);

                // Limpa selects com select2 se houver
                form.find('select').val(null).trigger('change');

                // Limpa textareas
                form.find('textarea').val('');

                return true;
            } 

            function save{$this->attactModel}(){
                $('#overlay-form-{$this->uniqueId}').show();
                var formData = $("#form-{$this->uniqueId}").serialize();
                console.log(formData);
                $.ajax({
                    type: "POST",
                    url: "{$this->saveUrl}",
                    data: formData,
                }).done(function(response) {       
                    if(response.success) {
                        toastr.success("Save!");
                        //clearForms{$this->attactModel}();
                        modal_{$this->attactModel}.hide();
                        $.pjax.reload({container: "#list-{$this->uniqueId}-grid", async: false});
                        {$this->callBack}
                    } else {
                        toastr.error("Error on save!");
                    }
                }).fail(function (response) {
                    toastr.error("Error on add!");
                }).always(function (response) {
                    $('#overlay-form-{$this->uniqueId}').hide();
                });
            }

            function get{$this->attactModel}(e) {

                let el = $(e);

                object = el.children("i");
                let old_class = el.children("i").attr('class');
                object.removeClass(old_class);
                object.addClass('fas fa-sync fa-spin');
                
                {$this->editCallBefore}

                $.ajax({
                    type: "POST",
                    url: el.data('link'),
                }).done(function(response) {     
                    if(response == null){
                        toastr.error("Error on load {$lower}!");
                        return false;
                    } else {
                        Object.entries(response).forEach(([key, value]) => {
                            var el = $(`#{$this->uniqueId}-\${key}`);
                            
                            if(el.attr('type') == 'checkbox') {
                                if (value === 1) {
                                    el.prop('checked', true);
                                } else {
                                    el.prop('checked', false);
                                }
                            } else if (el.is('select')) {
                                el.val(value).trigger('change');
                            } else {
                                let aceDiv = document.querySelector(`#editor_\${el.attr('id')}`);
                                if (aceDiv && aceDiv.classList.contains('ace_editor')) {
                                    let editor = ace.edit(`editor_\${el.attr('id')}`);
                                    editor.setValue(value, -1);
                                } else {
                                    el.val(value);
                                }
                            }
                        });
                        modal_{$this->attactModel}.show();
                        {$this->editCallBack}
                    }
                }).fail(function (response) {
                    toastr.error("Error on remove {$lower}!");
                }).always(function (response) {
                    object.removeClass('fas fa-sync fa-spin');
                    object.attr('class',old_class);
                });
            }

            function status{$this->attactModel}(e) {

                let el = $(e);

                object = el.children("i");
                let old_class = el.children("i").attr('class');
                object.removeClass(old_class);
                object.addClass('fas fa-sync fa-spin');

                $.ajax({
                    type: "POST",
                    url: el.data('link'),
                }).done(function(response) {     
                    if(response == null){
                        toastr.error("Error on load {$lower}!");
                        return false;
                    } else {
                        if(response.success) {
                            toastr.success("Status Changed!");
                            modal_{$this->attactModel}.hide();
                            $.pjax.reload({container: "#list-{$this->uniqueId}-grid", async: false});
                            {$this->callBack}
                        } else {
                            toastr.error("Error on save!");
                        }
                    }
                }).fail(function (response) {
                    toastr.error("Error on remove {$lower}!");
                }).always(function (response) {
                    object.removeClass('fas fa-sync fa-spin');
                    object.attr('class',old_class);
                });
            }

            function remove{$this->attactModel}(e) {

                let el = $(e);
                if (confirm('You really can remove this {$lower}?')) {

                    object = el.children("i");
                    let old_class = el.children("i").attr('class');
                    object.removeClass(old_class);
                    object.addClass('fas fa-sync fa-spin');

                    $.ajax({
                        type: "POST",
                        url: el.data('link'),
                    }).done(function(response) {     
                        if(response == 0){
                            toastr.error("Error on remove {$lower}!");
                            return false;
                        }
                        toastr.success("Removed!");
                        $.pjax.reload({container: "#list-{$this->uniqueId}-grid", async: false});
                        {$this->delCallBack}
                    }).fail(function (response) {
                        toastr.error("Error on remove {$lower}!");
                    }).always(function (response) {
                        object.removeClass('fas fa-sync fa-spin');
                        object.attr('class',old_class);
                    });
                }
                return false;
            }
        });
        JS);
        $field_str = '';

        $button = Html::a('<i class="fas fa-plus-square"></i> Novo', "javascript:modal_{$this->attactModel}.show();{$this->newCallBack}", ['class' => 'btn btn-success', 'id' => "btn-show-{$this->uniqueId}"]);
        $button_save = Yii::t('app', "Save");
        $button_cancel = Yii::t('app', 'Cancel');
        $begin = <<< HTML
            <!-- Modal -->
            <div class="modal fade" id="save-{$this->uniqueId}" data-bs-backdrop="static" data-bs-keyboard="true" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                <div class="modal-dialog {$this->modalSize}">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="staticBackdropLabel">{$this->title}</h5>
                            <button type="button" class="btn-close" onclick="javascript:modal_{$this->attactModel}.hide();" aria-label="Close"></button>
                        </div>
                        <div id="overlay-form-{$this->uniqueId}" class="overlay" style="height: 100%;position: absolute;width: 100%;z-index: 3000;display:none;top:0;left:0;">
                            <div class="fa-3x">
                                <i class="fas fa-sync fa-spin"></i>
                            </div>
                        </div>
                        <div class="modal-body" style="font-size:1em;">
        HTML;

        $end = <<< HTML
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="javascript:modal_{$this->attactModel}.hide();"> {$button_cancel} </button>
                            <button id="btn-save-{$this->uniqueId}" onclick="save{$this->attactModel}()" type="button" class="btn btn-success"><i class="fas fa-plus-circle mr-2 icon"></i> {$button_save} </button>
                        </div>
                    </div>
                </div>
            </div>
        HTML;

        echo $begin;
        $form = ActiveForm::begin(['id' => "form-{$this->uniqueId}"]);
        $model = new $this->attactClass();
        $field_str .=  $form->field($model, 'id')->hiddenInput(['id' => "{$this->uniqueId}-id", 'maxlength' => true])->label(false);

        foreach ($this->fields as $key => $field) {
            $field_str .= '<div class="col-md-12">';

            $before = $field['before'] ?? '';
            $after = $field['after'] ?? '';
            $fieldId = "{$this->uniqueId}-{$field['name']}";

            $inputOptions = array_merge($this->options, [
                'id' => $fieldId,
                'maxlength' => true,
                'value' => $field['value'] ?? '',
            ]);

            $field_str .= $before;

            if ($field['type'] == 'text')
                $field_str .= $form->field($model, $field['name'])->textInput(
                    array_merge($this->options, ['id' => "{$this->uniqueId}-{$field['name']}", 'maxlength' => true, 'value' => $field['value'] ?? ''])
                );
            else if ($field['type'] == 'textarea')
                $field_str .=  $form->field($model, $field['name'])->textarea(
                    array_merge($this->options, ['id' => "{$this->uniqueId}-{$field['name']}", 'maxlength' => true, 'value' => $field['value'] ?? ''])
                );
            else if ($field['type'] == 'ace')
                $field_str .=  AceEditor::widget([
                    'model' => $model,
                    'attribute' => $field['name'],
                    'mode' => $field['mode'] ?? 'html',
                    'theme' => $field['theme'] ?? 'twilight',
                    'height' => $field['height'] ?? '500px',
                    'options' => [
                        'id' => "{$this->uniqueId}-{$field['name']}",
                    ],
                ]);   
            else if ($field['type'] == 'number')
                $field_str .=  $form->field($model, $field['name'])->input(
                    'number',
                    array_merge($this->options, ['id' => "{$this->uniqueId}-{$field['name']}", 'maxlength' => true, 'value' => $field['value'] ?? ''])
                );
            else if ($field['type'] == 'hidden')
                $field_str .=  $form->field($model, $field['name'])->hiddenInput(
                    array_merge($this->options, ['id' => "{$this->uniqueId}-{$field['name']}", 'maxlength' => true, 'value' => $field['value'] ?? ''])
                )->label(false);
            else if ($field['type'] == 'checkbox')
                $field_str .=  $form->field($model, $field['name'])->checkbox(
                    array_merge($this->options, ['id' => "{$this->uniqueId}-{$field['name']}",])
                );
            else if ($field['type'] == 'dropdown') {
                $field_str .=  $form->field($model, $field['name'])->dropDownList(
                    $field['value'] ?? '',
                    array_merge($this->options, ['class' => 'form-control dropdown', 'id' => "{$this->uniqueId}-{$field['name']}"])
                );
            } else if ($field['type'] == 'select2') {
                $field_str .= $form->field($model, $field['name'])->dropDownList(
                    $field['value'] ?? [],
                    array_merge($this->options, [
                        'id' => "{$this->uniqueId}-{$field['name']}",
                        'class' => 'form-control dropdown-select2 select2',
                        'prompt' => 'Selecione',
                        'data-placeholder' => 'Selecione',
                    ])
                );
            }
            $field_str .= $after;
            $field_str .= '</div>';
        }
        echo $field_str;
        ActiveForm::end();
        echo $end;

        $gridView = ResponsiveGridView::widget([
            'id' => "grid-{$this->uniqueId}",
            'dataProvider' =>  $this->dataProvider,
            'columns' => $columns
        ]);

        $head = <<< HTML
            <div class="card mt-3" id="list-{$this->uniqueId}">
    
                <div class="card-header">
                    <h3 class="card-title">{$this->title}</h3>
                </div>
    
                <div class="card-body">
                    <p>
                        {$button}
                    </p>
                    <div class="row">
                        <div class="col-md-12">
    
                            <div id='overlay-{$this->uniqueId}' class='overlay' style='display:none;height: 100%;position: absolute;width: 100%;z-index: 3000;top: 0;left: 0;background: #0000004f;'>
                                <div class='d-flex align-items-center'>
                                    <strong> Loading... </strong>
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
        Pjax::begin(['id' => "list-{$this->uniqueId}-grid"]);
        echo $gridView;
        Pjax::end();
        echo $footer;
    }
}
