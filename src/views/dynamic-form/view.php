<?php

use croacworks\essentials\models\FormField;
use croacworks\essentials\enums\FormFieldType;
use weebz\yii2basics\helpers\ModelHelper;
use weebz\yii2basics\widgets\AppendModel;
use yii\helpers\ArrayHelper;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\DynamicForm */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Dynamic Forms'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

\yii\web\YiiAsset::register($this);
$order = (FormField::find()->where(['dynamic_form_id' => $model])->max('`order`') ?? 0) + 1;

$script = <<< JS

var fieldSelectedValue = null;

$('#formfieldsAppend-type').on('change', function() {
    var typeText  = $('#formfieldsAppend-type option:selected').text();
    showFields(typeText);
});

$('#formfieldsAppend-model_class').on('change', function() {
    var classModel = $(this).val();
    
    $.getJSON('/dynamic-form/get-fields', {class: classModel}, function(data) {
        var options = '<option value="">Selecione um campo...</option>';
        if (data.results) {
            data.results.forEach(function(item) {
                options += '<option value="' + item.id + '">' + item.text + '</option>';
            });
        }

        $('#formfieldsAppend-model_field').html(options);

        if(fieldSelectedValue !== null){
            $('#formfieldsAppend-model_field').val(fieldSelectedValue).trigger('change');
        }
    });
});

window.showFields = function(typeText) {
    
    $('.field-formfieldsAppend-sql').hide();
    $('.field-formfieldsAppend-model_class').hide();
    $('.field-formfieldsAppend-model_field').hide();
    $('.field-formfieldsAppend-model_criteria').hide();
    
    if(typeText === 'Model'){
        $('.field-formfieldsAppend-model_class').show();
        $('.field-formfieldsAppend-model_field').show();
        $('.field-formfieldsAppend-model_criteria').show();
    } else if(typeText === 'Script SQL'){
        $('.field-formfieldsAppend-sql').show();
    }
}

window.hideFields = function() {
    $('.field-formfieldsAppend-sql').hide();
    $('.field-formfieldsAppend-model_class').hide();
    $('.field-formfieldsAppend-model_field').hide();
    $('.field-formfieldsAppend-model_criteria').hide();
}

window.upOrder = function() {
    const rowCount = $('#grid-formfieldsAppend table tbody tr').length;
    $('#formfieldsAppend-order').val(rowCount);
}

window.edit = function(response) {
    hideFields();
    loadItemsFromInput();
    if(response.model_class !== undefined && response.model_class !== '') {
        console.log(response.model_class);
        fieldSelectedValue = response.model_field;
        $('#formfieldsAppend-model_class').trigger('change');
        $('#formfieldsAppend-type').trigger('change');
    }
}

JS;

$this->registerJs($script);
// $this->registerCss("
//     #modal-add-item {
//         z-index: 1080 !important;
//     }
// ");
$scriptItems = <<<JS
var itemsArray = [];

$('#formfieldsAppend-add-item').on('click', function() {
    $('#modal-add-item').modal('show');
});

$('#btn-add-item').on('click', function() {
    var value = $('#item-value').val().trim();
    var label = $('#item-label').val().trim();

    if (value === '' || label === '') return;

    itemsArray.push({value: value, label: label});
    
    renderItemList();

    $('#item-value').val('');
    $('#item-label').val('');
    const modalEl = document.getElementById('modal-add-item');
    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
    modalInstance.hide();
});

function renderItemList() {
    let html = '<ul class="list-group">';
    let itemStrs = [];

    itemsArray.forEach(function(item, index) {
        itemStrs.push(item.value + ':' + item.label);
        html += '<li class="list-group-item d-flex justify-content-between align-items-center">' +
                item.value + ': ' + item.label +
                '<button class="btn btn-sm btn-danger btn-remove-item" data-index="' + index + '">x</button></li>';
    });

    html += '</ul>';
    $('#item-list').html(html);
    $('#formfieldsAppend-items').val(itemStrs.join(';'));
}

// Remover item
$(document).on('click', '.btn-remove-item', function() {
    var index = $(this).data('index');
    itemsArray.splice(index, 1);
    renderItemList();
});

$('#formfieldsAppend-add-item').on('click', function () {
    const modalId = 'modal-add-item-' + Date.now(); // ID único

    const modalHtml = `
    <div class="modal fade" id="\${modalId}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

          <div class="modal-header">
            <h5 class="modal-title">Novo Item</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>

          <div class="modal-body">
            <div class="mb-3">
                <label for="\${modalId}-value" class="form-label">Valor</label>
                <input type="text" class="form-control" id="\${modalId}-value">
            </div>
            <div class="mb-3">
                <label for="\${modalId}-label" class="form-label">Rótulo</label>
                <input type="text" class="form-control" id="\${modalId}-label">
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            <button type="button" class="btn btn-success" id="\${modalId}-btn-add">Adicionar</button>
          </div>

        </div>
      </div>
    </div>`;

    // Adiciona no body
    $('body').append(modalHtml);

    // Inicializa e mostra o modal
    const modalElement = document.getElementById(modalId);
    const modalInstance = new bootstrap.Modal(modalElement);
    modalInstance.show();

    // Botão adicionar
    $(document).on('click', `#\${modalId}-btn-add`, function () {
        const value = $(`#\${modalId}-value`).val();
        const label = $(`#\${modalId}-label`).val();

        if (value === '' || label === '') return;

        itemsArray.push({value: value, label: label});
        renderItemList();

        modalInstance.hide(); // isso dispara hidden.bs.modal
    });

    // Remover modal do DOM ao fechar (cobre backdrop também)
    $(`#\${modalId}`).on('hidden.bs.modal', function () {
        $(this).remove();
    });
});

window.loadItemsFromInput = function () {
    itemsArray = [];

    const itemsRaw = $('#formfieldsAppend-items').val();

    if (!itemsRaw) {
        $('#item-list').html('');
        return;
    }

    itemsRaw.split(';').forEach(pair => {
        const [value, label] = pair.split(':');
        if (value && label) {
            itemsArray.push({ value: value.trim(), label: label.trim() });
        }
    });

    renderItemList();
};

window.setFormId = function () {
    $('#formfieldsAppend-dynamic_form_id').val('{$model->id}');
    upOrder();
};

JS;

$this->registerJs($scriptItems, $this::POS_END);

$formats = [
    'raw'       => 'raw',
    'text'      => 'text',
    'ntext'     => 'ntext',
    'html'      => 'html',
    'email'     => 'email',
    'image'     => 'image',
    'url'       => 'url',
    'boolean'   => 'boolean',
    'date'      => 'date',
    'time'      => 'time',
    'datetime'  => 'datetime',
    'currency'  => 'currency',
    'decimal'   => 'decimal',
    'percent'   => 'percent',
];

$extra = [
    [
        'controller' => 'dynamic-form',
        'action' => 'show',
        'icon' => '<i class="fas fa-plus-circle mr-2"></i>',
        'text' => Yii::t('app', 'Show') . ' ' . Yii::t('app', 'Show'),
        'link' => "/dynamic-form/show?id={$model->id}",
        'options' =>                    [
            'id' => 'btn-show',
            'class' => 'btn btn-default btn-block-m',
            'data-fancybox' => '',
            'data-type' => "iframe",
            'data-custom-class' => "fancybox-iframe",
            'onclick' => "hideFields();"
        ],
    ]
];

?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <p>
                        <?= \weebz\yii2basics\widgets\DefaultButtons::widget(['controller' => 'DynamicForm', 'model' => $model, 'verGroup' => false, 'extras' => $extra]) ?>
                    </p>
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            'name',
                            'description',
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
        'uniqueId' => 'formfieldsAppend',
        'title' => 'Form Fields',
        'attactModel' => 'FormField',
        'controller' => 'form-field',
        'callBack' => 'upOrder();',
        'newCallBack' => 'loadItemsFromInput();setFormId();',
        'editCallBack' => 'edit(response);',
        'editCallBefore' => 'loadItemsFromInput();',
        'template' => '{edit}{remove}',
        'attactClass' => 'app\\models\\FormField',
        'order' => 1,
        'orderField' => 'order',
        'orderModel' => 'FormField',
        'dataProvider' => new \yii\data\ActiveDataProvider([
            'query' => $model->getFormFields()->orderBy(['order' => SORT_ASC]),
        ]),
        'showFields' => ['label','name','format','type','description','default','items',
        'model_class', 'model_field', 'model_criteria', 'sql', 'script', 'show:boolean', 'status:boolean'],
        'fields' =>
        [
            [
                'name' => 'dynamic_form_id',
                'type' => 'hidden',
                'value' => $model->id
            ],
            [
                'name' => 'order',
                'type' => 'hidden',
                'value' => $order
            ],
            [
                'name' => 'label',
                'type' => 'text'
            ],
            [
                'name' => 'name',
                'type' => 'text'
            ],
            [
                'name' => 'type',
                'type' => 'dropdown',
                'value' => ArrayHelper::map(FormFieldType::getList(), 'id', 'name')
            ],
            [
                'name' => 'format',
                'type' => 'dropdown',
                'value' => $formats
            ],
            [
                'name' => 'description',
                'type' => 'text'
            ],
            [
                'name' => 'default',
                'type' => 'text'
            ],
            [
                'name' => 'items',
                'type' => 'hidden',
                'options' => [
                    'id' => 'formfieldsAppend-items'
                ],
                'after' => '<button type="button" id="formfieldsAppend-add-item" class="btn btn-sm btn-primary mt-1">
                                <i class="fas fa-plus"></i> Adicionar Item
                            </button>
                            <div id="item-list" class="mt-2"></div>'
            ],
            [
                'name' => 'model_class',
                'type' => 'dropdown',
                'value' => ModelHelper::getAllModelClasses()
            ],
            [
                'name' => 'model_field',
                'type' => 'dropdown',
                'value' => []
            ],
            [
                'name' => 'model_criteria',
                'type' => 'text'
            ],
            [
                'name' => 'sql',
                'type' => 'textarea'
            ],
            [
                'name' => 'script',
                'type' => 'textarea'
            ],
            [
                'name' => 'show',
                'type' => 'checkbox'
            ],
            [
                'name' => 'status',
                'type' => 'checkbox'
            ]
        ]
    ]); ?>