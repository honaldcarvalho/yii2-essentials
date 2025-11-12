<?php

namespace croacworks\essentials\widgets;

use Yii;
use yii\base\Widget;
use yii\base\DynamicModel;
use yii\helpers\ArrayHelper;
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
use croacworks\essentials\models\FormField;
use croacworks\essentials\enums\FormFieldType;
use croacworks\essentials\models\File;
use croacworks\essentials\widgets\UploadImageInstant;

class DynamicFormWidget extends Widget
{
    public $formId;
    public $ajax = true;
    public $model;
    public $file = null;
    public $action = null;
    public $copyToForm = false;

    /** @var callable|null fn(int $fileId): string|array rota/URL para abrir arquivo */
    public $fileUrlCallback = null;

    /** @var bool Mostrar bloco do anexo atual acima do input file */
    public $showCurrentFile = true;

    /** @var callable|null fn(int $fileId): string|array rota/URL direto da imagem */
    public $pictureUrlCallback = null;

    public $showSave = true;

    public function init(){
        if ($this->fileUrlCallback === null) {
            $this->fileUrlCallback = static function (int $fileId) {
                return ['/file/view', 'id' => $fileId];
            };
        }

        if ($this->pictureUrlCallback === null) {
            // tente rota "raw"; ajuste se sua app servir a imagem por outra rota
            $this->pictureUrlCallback = static function (int $fileId) {
                return ['/file/view', 'id' => $fileId];
            };
        }
    }

    private function parseOptions($optionsString)
    {
        $options = [];
        $pairs = explode(';', $optionsString);
        foreach ($pairs as $pair) {
            if (strpos($pair, ':') === false) continue;
            [$key, $val] = explode(':', $pair, 2);
            $options[trim($key)] = trim($val);
        }
        return $options;
    }

    private function normalizeValue($val)
    {
        return ($val === '' || $val === [] || $val === false) ? null : $val;
    }

    private function applyDefaultValue(array $options, $default): array
    {
        $isEdit = $this->model instanceof \croacworks\essentials\models\FormResponse;
        if (!isset($options['value']) && !$isEdit && $default !== null) {
            $options['value'] = $default;
        }
        return $options;
    }

    public function run()
    {
        $formId = $this->formId;
        $isEdit = $this->model instanceof \croacworks\essentials\models\FormResponse;
        $responseData = $isEdit ? $this->model->response_data ?? [] : [];

        $fields = FormField::find()
            ->where(['dynamic_form_id' => $formId, 'status' => 1])
            ->orderBy(['order' => SORT_ASC])
            ->all();

        $visibleFields = array_filter($fields, fn($f) => $f->show);

        $allAttributes = [];
        foreach ($fields as $field) {
            $val = $responseData[$field->name] ?? $field->default ?? null;
            $allAttributes[$field->name] = $this->normalizeValue($val);
        }

        $visibleAttributeNames = array_map(fn($f) => $f->name, $visibleFields);
        $visibleAttributes = array_intersect_key($allAttributes, array_flip($visibleAttributeNames));

        $model = new DynamicModel($visibleAttributes);
        foreach ($visibleAttributes as $attr => $val) {
            $model->addRule($attr, 'safe');
        }

        ob_start();
        if ($this->action === null) {
            $this->action = ['form-response/update-json', 'id' => $this->model->id ?? null];
        }

        $form = ActiveForm::begin([
            'id' => 'dynamic-form-' . $formId,
            'action' => $this->action ?? ['form-response/update-json', 'id' => $this->model->id ?? null],
            'method' => 'post',
            'options' => [
                'enctype'  => 'multipart/form-data',
                'data-pjax'=> 0,
            ],
            'enableClientScript' => true,
        ]);

        foreach ($visibleFields as $field) {
            $name = $field->name;
            $type = $field->type;
            $default = $field->default;
            $items = [];

            $options = [];
            if (!empty($field->options)) {
                $options = $this->parseOptions($field->options);
            }
            $options = $this->applyDefaultValue($options, $default);

            if (!empty($field->items)) {
                foreach (explode(';', $field->items) as $item) {
                    [$val, $label] = explode(':', $item);
                    $items[$val] = $label;
                }
            }

            switch ((int) $field->type) {
                case FormFieldType::TYPE_TEXTAREA:
                    echo $form->field($model, $name)->textarea($options);
                    break;

                case FormFieldType::TYPE_DATE:
                    echo $form->field($model, $name)->input('date', $options);
                    break;

                case FormFieldType::TYPE_PICTURE:
                    $name      = $field->name;
                    $label     = $field->label ?: $name;
                    $inputName = "DynamicModel[{$name}]";

                    // valor atual vindo do JSON do FormResponse
                    $data = is_array($this->model?->response_data)
                        ? $this->model->response_data
                        : (is_string($this->model?->response_data) ? json_decode($this->model?->response_data, true) : []);
                    $currentId = (int)($data[$name] ?? 0);

                    // bloco HTML
                    echo '<div class="mb-3">';
                    echo '<label class="form-label">'.\yii\helpers\Html::encode($label).'</label>';
                    
                    $url = null;

                    if ($this->showCurrentFile) {
                        if ($currentId > 0) {
                            $url = call_user_func($this->fileUrlCallback, $currentId);
                            echo '<div class="d-flex align-items-center gap-2 mb-2">';
                            echo '<span class="badge bg-info">Anexo atual: #'.(int)$currentId.'</span>';
                            echo \yii\helpers\Html::a('Abrir', $url, [
                                'class'  => 'btn btn-sm btn-outline-primary',
                                'target' => '_blank',
                                'rel'    => 'noopener',
                            ]);
                            echo '</div>';
                        } else {
                            echo '<div class="text-muted small mb-2">(sem arquivo)</div>';
                        }
                    }
                    $file = null;
                    if($currentId){
                        $file = File::findOne($currentId);
                    }
                    echo $form->field($model, $name)
                            ->fileInput([
                                'id' => \yii\helpers\Html::getInputId($model, $name),
                                'accept' => 'image/*',
                                'style' => 'display:none'
                            ])->label(false);

                    echo UploadImageInstant::widget([
                            'mode'        => 'defer',
                            'model'       => $model,
                            'modelId'     => $field->id,
                            'attribute'   => $name,
                            'fileInputId' => \yii\helpers\Html::getInputId($model, $name),
                            'imageUrl'    => $file?->url ?? '',
                            'aspectRatio' => '1',
                    ]);

                    $clearName = "DynamicModel[{$name}_clear]";
                    $clearId   = "clear-{$name}";
                    echo '<div class="form-check mt-2">';
                    echo '<input class="form-check-input" type="checkbox" id="'.$clearId.'" name="'.$clearName.'" value="1">';
                    echo '<label class="form-check-label" for="'.$clearId.'">Remover arquivo</label>';
                    echo '</div>';

                    echo '</div>';
                    break;


                /*** FIM */    
                case FormFieldType::TYPE_FILE:
                    $name      = $field->name;
                    $label     = $field->label ?: $name;
                    $inputName = "DynamicModel[{$name}]";

                    // valor atual vindo do JSON do FormResponse
                    $data = is_array($this->model?->response_data)
                        ? $this->model->response_data
                        : (is_string($this->model?->response_data) ? json_decode($this->model?->response_data, true) : []);
                    $currentId = (int)($data[$name] ?? 0);

                    // bloco HTML
                    echo '<div class="mb-3">';
                    echo '<label class="form-label">'.\yii\helpers\Html::encode($label).'</label>';

                    if ($this->showCurrentFile) {
                        if ($currentId > 0) {
                            $url = call_user_func($this->fileUrlCallback, $currentId);
                            echo '<div class="d-flex align-items-center gap-2 mb-2">';
                            echo '<span class="badge bg-info">Anexo atual: #'.(int)$currentId.'</span>';
                            echo \yii\helpers\Html::a('Abrir', $url, [
                                'class'  => 'btn btn-sm btn-outline-primary',
                                'target' => '_blank',
                                'rel'    => 'noopener',
                            ]);
                            echo '</div>';
                        } else {
                            echo '<div class="text-muted small mb-2">(sem arquivo)</div>';
                        }
                    }

                    echo '<input type="file" name="'.$inputName.'" class="form-control"/>';

                    $clearName = "DynamicModel[{$name}_clear]";
                    $clearId   = "clear-{$name}";
                    echo '<div class="form-check mt-2">';
                    echo '<input class="form-check-input" type="checkbox" id="'.$clearId.'" name="'.$clearName.'" value="1">';
                    echo '<label class="form-check-label" for="'.$clearId.'">Remover arquivo</label>';
                    echo '</div>';

                    echo '</div>';
                    break;

                case FormFieldType::TYPE_DATETIME:
                    echo $form->field($model, $name)->input('datetime-local', $options);
                    break;

                case FormFieldType::TYPE_SELECT:
                    echo $form->field($model, $name)->dropDownList($items, array_merge($options, ['prompt' => 'Selecione...']));
                    break;

                case FormFieldType::TYPE_MULTIPLE:
                    echo $form->field($model, $name)->dropDownList($items, array_merge($options, ['multiple' => true]));
                    break;

                case FormFieldType::TYPE_CHECKBOX:
                    echo $form->field($model, $name)->checkboxList($items, $options);
                    break;

                case FormFieldType::TYPE_EMAIL:
                    echo $form->field($model, $name)->textInput(array_merge($options, ['type' => 'email']));
                    break;

                case FormFieldType::TYPE_PHONE:
                    echo $form->field($model, $name)->textInput(
                        array_merge(
                            $options,
                            [
                                'class' => 'form-control phone-mask',
                                'placeholder' => '(00) 00000-0000'
                            ]
                        )
                    );
                    break;

                case FormFieldType::TYPE_IDENTIFIER:
                    echo $form->field($model, $name)->textInput(
                        array_merge(
                            $options,
                            [
                                'class' => 'form-control cpf-mask',
                                'placeholder' => 'CPF ou CNPJ'
                            ]
                        )
                    );
                    break;

                case FormFieldType::TYPE_NUMBER:
                    echo $form->field($model, $name)->textInput(array_merge($options, ['type' => 'number']));
                    break;

                case FormFieldType::TYPE_MODEL:
                    $list = [];
                    if (class_exists($field->model_class) && $field->model_field) {
                        $query = $field->model_class::find();
                        if (!empty($field->model_criteria)) {
                            $query->andWhere($field->model_criteria);
                        }
                        $list = ArrayHelper::map($query->all(), 'id', $field->model_field);
                    }
                    echo $form->field($model, $name)->dropDownList($list, array_merge($options, ['prompt' => 'Selecione...']));
                    break;

                case FormFieldType::TYPE_SQL:
                    $list = [];
                    if (!empty($field->sql)) {
                        try {
                            $data = Yii::$app->db->createCommand($field->sql)->queryAll();
                            foreach ($data as $row) {
                                $list[$row['id']] = $row['label'];
                            }
                        } catch (\Throwable $e) {
                            Yii::error("Erro na query SQL do campo {$name}: " . $e->getMessage(), __METHOD__);
                        }
                    }
                    echo $form->field($model, $name)->dropDownList($list, array_merge($options, ['prompt' => 'Selecione...']));
                    break;
                    
                case FormFieldType::TYPE_HIDDEN:
                    echo $form->field($model, $name)->hiddenInput($options);
                    break;
                default:

                case FormFieldType::TYPE_TEXT:
                    echo $form->field($model, $name)->textInput($options);
                    break;
            }
        }

        if($this->showSave)
            echo Html::submitButton(
                '<span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span> ' . Yii::t('app', 'Salvar'),
                ['class' => 'btn btn-success', 'id' => 'btn-submit-dynamic-form']
            );
            
        ActiveForm::end();

        $output = ob_get_clean();
        $ajax = $this->ajax ? 1 : 0;

        if ($this->copyToForm !== false) {
            $copyJs = <<<JS
            (function () {
                // Copia/move campos por [name] do form dinâmico -> form principal
                function transferForm(fromSelector, toSelector, options) {
                    var move = options && options.move === true;
                    var src = $(fromSelector), dst = $(toSelector);

                    // Remover clones anteriores
                    dst.find('[data-dfw-clone="1"]').remove();

                    src.find('input, textarea, select').each(function () {
                        var el = this;
                        var name = $(el).attr('name');
                        if (!name) return;

                        var type = (el.type || '').toLowerCase();
                        var tag = (el.tagName || '').toLowerCase();

                        // Arquivo não dá pra clonar por segurança do browser; deixe o upload ir no form do dinâmico (AJAX)
                        if (type === 'file') return;

                        // Checkbox (grupo ou single)
                        if (type === 'checkbox') {
                            var group = src.find('input[type="checkbox"][name="' + name + '"]');
                            if (group.length > 1) {
                                group.filter(':checked').each(function () {
                                    var h = document.createElement('input');
                                    h.type = 'hidden';
                                    h.name = name;
                                    h.value = this.value;
                                    h.setAttribute('data-dfw-clone', '1');
                                    dst[0].appendChild(h);
                                });
                                if (move) group.prop('checked', false).trigger('change');
                            } else {
                                var h = document.createElement('input');
                                h.type = 'hidden';
                                h.name = name;
                                h.value = $(el).is(':checked') ? ($(el).val() || '1') : '';
                                h.setAttribute('data-dfw-clone', '1');
                                dst[0].appendChild(h);
                                if (move) $(el).prop('checked', false).trigger('change');
                            }
                            return;
                        }

                        // Radio
                        if (type === 'radio') {
                            var selected = src.find('input[type="radio"][name="' + name + '"]:checked');
                            if (selected.length) {
                                var h = document.createElement('input');
                                h.type = 'hidden';
                                h.name = name;
                                h.value = selected.val();
                                h.setAttribute('data-dfw-clone', '1');
                                dst[0].appendChild(h);
                                if (move) selected.prop('checked', false).trigger('change');
                            }
                            return;
                        }

                        // Select
                        if (tag === 'select') {
                            if (el.multiple) {
                                Array.from(el.selectedOptions).forEach(function (opt) {
                                    var h = document.createElement('input');
                                    h.type = 'hidden';
                                    h.name = name;
                                    h.value = opt.value;
                                    h.setAttribute('data-dfw-clone', '1');
                                    dst[0].appendChild(h);
                                });
                                if (move) $(el).val([]).trigger('change');
                            } else {
                                var h = document.createElement('input');
                                h.type = 'hidden';
                                h.name = name;
                                h.value = $(el).val() ?? '';
                                h.setAttribute('data-dfw-clone', '1');
                                dst[0].appendChild(h);
                                if (move) $(el).val('').trigger('change');
                            }
                            return;
                        }

                        // Inputs de texto/textarea/hidden/number/etc.
                        var h = document.createElement('input');
                        h.type = 'hidden';
                        h.name = name;
                        h.value = $(el).val() ?? '';
                        h.setAttribute('data-dfw-clone', '1');
                        dst[0].appendChild(h);
                        if (move) $(el).val('').trigger('change');
                    });
                }

                var mainFormSel = '{$this->copyToForm}';
                var dynFormSel = '#dynamic-form-{$formId}';

                var mainForm = document.querySelector(mainFormSel);
                if (!mainForm) return;

                mainForm.addEventListener('submit', function () {
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.triggerSave) tinyMCE.triggerSave();
                    transferForm(dynFormSel, mainFormSel, { move: true });
                });
            })();
            JS;
            Yii::$app->view->registerJs($copyJs);
        }


        $js = <<< JS

        $('#dynamic-form-{$formId}').on('submit', function (e) {
            if ($ajax === 0) return; // deixe o submit normal
            e.preventDefault();

            var form = $(this); // agora é o <form>
            var btn = form.find('#btn-submit-dynamic-form');
            var spinner = btn.find('.spinner-border');

            btn.prop('disabled', true);
            spinner.removeClass('d-none');

            var fd = new FormData(form[0]);

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: fd,
                contentType: false,
                processData: false,
                cache: false
            }).done(function (res) {
                btn.prop('disabled', false);
                spinner.addClass('d-none');
                if (res && res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: yii.t('app', 'Success'),
                        text: res.message || yii.t('app', 'Saved successfully.'),
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function () {
                        $('#modal-edit-response').modal('hide');
                        if ($.pjax) $.pjax.reload({ container: '#pjax-grid-responses', timeout: 3000 });
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: yii.t('app', 'Save error'),
                        html: (res && (res.error || res.errors)) ? (res.error || JSON.stringify(res.errors, null, 2)) : yii.t('app', 'Unknown error'),
                        customClass: { popup: 'text-start' }
                    });
                }
            }).fail(function () {
                btn.prop('disabled', false);
                spinner.addClass('d-none');
                Swal.fire(yii.t('app', 'Error'), yii.t('app', 'Could not save. Try again.'), 'error');
            });
        });

        $('#dynamic-form-{$formId} #btn-submit-dynamic-form').on('click', function (e) {
            if ($ajax === 1) e.preventDefault();
            $(this).closest('form').trigger('submit');
        });

        $('.cpf-mask').inputmask({
            mask: ['999.999.999-99', '99.999.999/9999-99'],
            keepStatic: true
        });
        JS;

        $this->registerInputMaskAssets();
        Yii::$app->view->registerJs($js);

        return $output;
    }

    protected function registerInputMaskAssets()
    {
        Yii::$app->view->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8/jquery.inputmask.min.js', [
            'depends' => [\yii\web\JqueryAsset::class],
        ]);
    }
}
