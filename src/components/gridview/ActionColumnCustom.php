<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace croacworks\essentials\components\gridview;

use Yii;
use yii\helpers\Html;
use croacworks\essentials\controllers\AuthorizationController as Authz;

class ActionColumnCustom extends \yii\grid\ActionColumn
{
    public $template = '{view}{update}{delete}';
    public $verGroup = true;
    /** ID do controller (rota), usado para montar URLs. Ex.: 'user' */
    public $controller = null;
    /** Mantido por compatibilidade, mas não é usado na autorização nova */
    public $path = null;
    public $model = null;
    public $order = false;
    public $uniqueId = null;
    public $orderField = 'order';
    public $orderModel = null;
    public $modelClass = null;

    /** NOVO: FQCN do controller para autorização. Ex.: app\controllers\UserController */
    public $controllerFQCN = null;

    /**
     * Initializes the default button rendering callbacks.
     */
    public function init(): void
    {
        if ($this->uniqueId === null) {
            $this->uniqueId = "$this->controller";
        }

        $this->contentOptions['class'] = 'action-column';

        if ($this->grid->filterModel !== null) {
            $class_path = get_class($this->grid->filterModel);
            $class_path_parts = explode('\\', $class_path);
            $class_name = end($class_path_parts);
            $this->model = $class_name;
            $this->modelClass = $this->grid->filterModel;
        }

        $this->grid->summaryOptions = ['class' => 'summary mb-2'];
        $this->grid->pager = ['class' => 'yii\bootstrap5\LinkPager'];
        parent::init();
    }

    protected function registerScript()
    {
        if ($this->controller == null) {
            $this->controller = Yii::$app->controller->id;
        }
        $order = 0;
        if ($this->order) {
            $order = 1;
        }

        $script = <<< JS
            function clearForms() {
                document.getElementById("form-{$this->uniqueId}")?.reset();
                $(':input').not(':button, :submit, :reset, :hidden, :checkbox, :radio').val('');
                $('#btn-add-translate').prop('disabled', false);
                $('select').val(null).trigger('change');
                return true;
            }

            // Mensagens em inglês + i18n
            function toastSuccessDefault()  { toastr.success(yii.t('app','Success!')); }
            function toastFailDefault()     { toastr.error(yii.t('app','Operation failed.')); }
            function toastOrderUpdated()    { toastr.success(yii.t('app','Order updated.')); }
            function toastOrderError()      { toastr.error(yii.t('app','Failed to update order. Please reload the page.')); }
            function toastLoadError(ctrl)   { toastr.error(yii.t('app','Error loading {controller}.', {controller: ctrl || 'data'})); }
            function toastRemoveError(ctrl) { toastr.error(yii.t('app','Error removing {controller}.', {controller: ctrl || 'data'})); }

            // SweetAlert helpers (i18n)
            function swalLoading(title) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: title || yii.t('app','Processing...'),
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                } else {
                    console.log('[Loader] ' + (title || yii.t('app','Processing...')));
                }
            }
            function swalClose() {
                if (typeof Swal !== 'undefined') Swal.close();
            }

            function get{$this->model}(e) {
                let el = $(e);
                let object = el.children("i");
                let old_class = object.attr('class');
                object.removeClass(old_class).addClass('fas fa-sync fa-spin');

                $.ajax({
                    type: "POST",
                    url: el.data('link'),
                }).done(function(response) {
                    if (response == null) {
                        toastLoadError("{$this->controller}");
                        return false;
                    } else {
                        Object.entries(response).forEach(([key, value]) => {
                            var inp = $("#{$this->controller}-" + key);
                            if (inp.attr('type') == 'checkbox') {
                                inp.prop('checked', value === 1);
                            } else if (inp.attr('type') == 'select') {
                                inp.val(value).trigger('change');
                            } else {
                                inp.val(value);
                            }
                        });
                        if (typeof window['modal_{$this->model}'] !== 'undefined') {
                            window['modal_{$this->model}'].show();
                        }
                    }
                }).fail(function () {
                    toastRemoveError("{$this->controller}");
                }).always(function () {
                    object.removeClass('fas fa-sync fa-spin').attr('class', old_class);
                });
            }

            function callAction(e, id, action) {
                let el = $(e);
                let object = el.children("i");
                let old_class = object.attr('class');

                el.prop('disabled', true);
                object.removeClass(old_class).addClass('fas fa-sync fa-spin');
                $('#overlay-{$this->uniqueId}').show();

                $.ajax({
                    method: "POST",
                    url: "/{$this->controller}/" + action + "?id=" + id
                }).done(function() {
                    toastSuccessDefault();
                    $.pjax.reload({container: "#grid-{$this->uniqueId}", async: false});
                }).fail(function () {
                    toastFailDefault();
                }).always(function () {
                    $('#overlay-{$this->uniqueId}').hide();
                    el.prop('disabled', false);
                    object.removeClass('fas fa-sync fa-spin').attr('class', old_class);
                });
            }

            // 🔹 Handler UNIVERSAL do botão {clone} — usa a actionClone padrão
            // Mostra um loading SweetAlert e redireciona (sem AJAX) para /<controller>/clone?id=<id>
            $(document).on('click', '.action-clone', function(ev){
                ev.preventDefault();
                const url = this.getAttribute('data-link');
                if (!url) return;

                swalLoading(yii.t('app','Cloning...'));
                // Redireciona para a actionClone universal do CommonController
                window.location.href = url;
            });

            $(function(){
                if({$order} == 1){
                    setSortable();
                }
                $(document).on('pjax:start', function() {
                    $('#overlay-{$this->uniqueId}').show();
                });
                $(document).on('pjax:complete', function() {
                    $('#overlay-{$this->uniqueId}').hide();
                });
            });
        JS;

                $script_order = <<< JS
            function updateOrder(){
                let items = [];

                $('#overlay-{$this->uniqueId}').show();
                $("#grid-{$this->uniqueId} .table tbody tr").each(function(){
                    items.push($(this).attr("data-key"));
                });

                $.ajax({
                    method: "POST",
                    url: '/{$this->controller}/order-model',
                    data: {'items': items, 'field': '{$this->orderField}', 'modelClass': '$this->orderModel'}
                }).done(function() {
                    toastOrderUpdated();
                    $.pjax.reload({container: "#grid-{$this->uniqueId}", async: false});
                    setSortable();
                }).fail(function () {
                    toastOrderError();
                }).always(function () {
                    $('#overlay-{$this->uniqueId}').hide();
                });
            }

            function setSortable(){
                jQuery("#grid-{$this->uniqueId} .table tbody").sortable({
                    update: function() { updateOrder(); }
                });
            }
        JS;

        $view = Yii::$app->view;

        if ($this->order)
            $view->registerJs($script_order, $view::POS_END);
        $view->registerJs($script, $view::POS_END);
    }

    protected function initDefaultButtons()
    {
        $this->initDefaultButton('status', 'status');
        $this->initDefaultButton('clone', 'fa-clone');
        $this->initDefaultButton('view', 'fa-eye');
        $this->initDefaultButton('edit', 'fa-pencil-alt');
        $this->initDefaultButton('update', 'fa-pencil-alt');
        $this->initDefaultButton('remove', 'fa-trash');

        $this->initDefaultButton('delete', 'fa-trash', [
            'data-confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
            'data-method'  => 'post',
        ]);

        $this->registerScript();
    }

    public function renderDataCellContent($model, $key, $index)
    {
        return Html::tag('div', parent::renderDataCellContent($model, $key, $index), ['class' => 'btn-group']);
    }

    /**
     * Initializes the default button rendering callback for single button.
     * @param string $name Button name as it's written in template
     * @param string $iconName The part of Bootstrap glyphicon class that makes it unique
     * @param array $additionalOptions Array of additional options
     * @since 2.0.11
     */
    protected function initDefaultButton($name, $iconName, $additionalOptions = [])
    {
        // Define controller id (rota) e path (legado) quando não informados
        if ($this->controller === null) {
            $controller_parts = explode('\\', get_class(Yii::$app->controller));
            if ($this->path === null) {
                if (count($controller_parts) == 4)
                    $this->path = "{$controller_parts[0]}/{$controller_parts[2]}";
                else
                    $this->path = "{$controller_parts[0]}";
            }
            $controller_parts = explode('Controller', end($controller_parts));
            $this->controller = strtolower($controller_parts[0]);
            if (($tranformed = self::addSlashUpperLower($controller_parts[0])) != false) {
                $this->controller = $tranformed;
            }
        }

        // Define FQCN do controller para autorização (padrão: controller atual)
        if ($this->controllerFQCN === null) {
            $this->controllerFQCN = get_class(Yii::$app->controller);
        }

        if (!isset($this->buttons[$name]) && strpos($this->template, '{' . $name . '}') !== false) {
            $this->buttons[$name] = function ($url, $model, $key) use ($name, $iconName, $additionalOptions) {

                $options = array_merge([
                    'title' => Yii::t('yii', ucfirst($name)),
                    'aria-label' => Yii::t('yii', ucfirst($name)),
                    'data-pjax' => '0',
                    'class' => 'btn btn-outline-secondary',
                ], $additionalOptions, $this->buttonOptions);

                $icon = Html::tag('i', '', ['class' => "fas $iconName"]);

                switch ($name) {
                    case 'view':
                        $link = Html::a($icon, $url, $options);
                        break;
                    case 'update':
                        $link = Html::a($icon, $url, $options);
                        break;
                    case 'delete':
                        $link = Html::a($icon, $url, $options);
                        break;
                    case 'clone':
                        $icon = Html::tag('i', '', ['class' => 'fas fa-clone']);
                        // mantém o endpoint padrão: /<controller>/clone?id=<id>
                        $link = Html::a(
                            $icon,
                            'javascript:;',
                            [
                                'data-link' => "/{$this->controller}/clone?id={$model->id}",
                                'class'     => 'btn btn-outline-secondary action-clone', // <- classe universal
                                'title'     => Yii::t('app', 'Clone'),
                                'aria-label' => Yii::t('app', 'Clone'),
                            ]
                        );
                        break;
                    case 'edit':
                        $link = Html::a($icon,  "javascript:;", ['data-link' => "/{$this->controller}/get-model?modelClass={$this->model}&id=$model->id", 'onclick' => "get{$this->model}(this);", 'class' => 'btn btn-outline-secondary']);
                        break;
                    case 'remove':
                        $link = Html::a($icon,  "javascript:;", ['onclick' => "callAction(this,{$model->id},'remove')", 'class' => 'btn btn-outline-secondary']);
                        break;
                    case 'status':
                        $link = Html::a('<i class="fas fa-toggle-' . (!$model->status ? 'off' : 'on') . '"></i>',  "javascript:;", ['onclick' => "callAction(this,{$model->id},'status')", 'class' => 'btn btn-outline-secondary']);
                        break;
                    default:
                        $link = Html::a($icon, $url, $options);
                        break;
                }

                // ---- AUTORIZAÇÃO (AuthorizationController + FQCN) ----
                if (!Authz::isMaster()) {
                    $can = Authz::verAuthorization($this->controllerFQCN, $name, $this->verGroup ? $model : null);
                    return $can ? $link : '';
                }
                return $link;
            };
        }
    }

    /**
     * Mantida para compatibilidade com o comportamento anterior:
     * Transforma "MyController" em rotas com possível slash (ex.: "my" ou "my-controller")
     * Retorna false se nenhuma transformação foi aplicada.
     */
    public static function addSlashUpperLower($controllerName)
    {
        // Implementação mínima baseada no padrão anterior.
        // Ajuste aqui se sua versão original tinha lógica específica.
        $id = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $controllerName));
        return $id ?: false;
    }
}
