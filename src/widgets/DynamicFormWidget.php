<?php

namespace croacworks\essentials\widgets;

use Yii;
use yii\base\Widget;
use yii\base\DynamicModel;
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
use croacworks\essentials\models\FormField;
use croacworks\essentials\enums\FormFieldType;
use croacworks\essentials\models\File;
use croacworks\essentials\models\FormResponse;
use croacworks\essentials\widgets\UploadImageInstant;
use yii\helpers\ArrayHelper;

class DynamicFormWidget extends Widget
{
    public $formId;
    public $ajax = true;
    public $model; // Instância de FormResponse
    public $action = null;

    /** @var ActiveForm|null Se informado, usa este form pai em vez de criar um novo */
    public $activeForm = null;

    public $fileUrlCallback = null;
    public $pictureUrlCallback = null;
    public $showCurrentFile = true;
    public $showSave = true;

    public function init()
    {
        parent::init();
        if ($this->fileUrlCallback === null) {
            $this->fileUrlCallback = static fn(int $fileId) => ['/file/view', 'id' => $fileId];
        }
        if ($this->pictureUrlCallback === null) {
            $this->pictureUrlCallback = static fn(int $fileId) => ['/file/view', 'id' => $fileId];
        }
    }

    private function normalizeValue($val)
    {
        return ($val === '' || $val === [] || $val === false) ? null : $val;
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

    public function run()
    {
        $formId = $this->formId;
        $isEdit = $this->model instanceof FormResponse;

        // ==============================================================================
        // CORREÇÃO: Garante que response_data seja um Array, decodificando se necessário
        // ==============================================================================
        $responseData = [];
        if ($isEdit && !empty($this->model->response_data)) {
            $raw = $this->model->response_data;
            if (is_array($raw)) {
                $responseData = $raw;
            } elseif (is_string($raw)) {
                $responseData = json_decode($raw, true);
                if (!is_array($responseData)) {
                    $responseData = [];
                }
            }
        }
        // ==============================================================================

        $fields = FormField::find()
            ->where(['dynamic_form_id' => $formId, 'status' => 1])
            ->orderBy(['order' => SORT_ASC])
            ->all();

        $visibleFields = array_filter($fields, fn($f) => $f->show);

        // Popula os atributos com os dados decodificados
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

        // Lógica para usar form pai ou criar novo
        if ($this->activeForm) {
            $form = $this->activeForm;
        } else {
            if ($this->action === null) {
                $this->action = ['form-response/update-json', 'id' => $this->model->id ?? null];
            }
            $form = ActiveForm::begin([
                'id' => 'dynamic-form-' . $formId,
                'action' => $this->action,
                'method' => 'post',
                'options' => ['enctype' => 'multipart/form-data', 'data-pjax' => 0],
                'enableClientScript' => true,
            ]);
        }

        foreach ($visibleFields as $field) {
            $name = $field->name;
            $options = !empty($field->options) ? $this->parseOptions($field->options) : [];

            // Valor padrão apenas se não estiver editando e o campo estiver vazio
            if (!isset($options['value']) && !$isEdit && $field->default !== null) {
                $options['value'] = $field->default;
            }

            $items = [];
            if (!empty($field->items)) {
                foreach (explode(';', $field->items) as $item) {
                    $parts = explode(':', $item);
                    if (count($parts) >= 2) {
                        $items[$parts[0]] = $parts[1];
                    } else {
                        $items[$item] = $item;
                    }
                }
            }

            switch ((int)$field->type) {
                case FormFieldType::TYPE_TEXTAREA:
                    echo $form->field($model, $name)->textarea($options);
                    break;
                case FormFieldType::TYPE_DATE:
                    echo $form->field($model, $name)->input('date', $options);
                    break;
                case FormFieldType::TYPE_DATETIME:
                    echo $form->field($model, $name)->input('datetime-local', $options);
                    break;
                case FormFieldType::TYPE_SELECT:
                    echo $form->field($model, $name)->dropDownList($items, array_merge($options, ['prompt' => Yii::t('app', 'Select...')]));
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
                case FormFieldType::TYPE_NUMBER:
                    echo $form->field($model, $name)->textInput(array_merge($options, ['type' => 'number']));
                    break;
                case FormFieldType::TYPE_HIDDEN:
                    echo $form->field($model, $name)->hiddenInput($options)->label(false);
                    break;

                case FormFieldType::TYPE_PICTURE:
                case FormFieldType::TYPE_FILE:
                    $label = $field->label ?: $name;
                    $currentId = (int)($responseData[$name] ?? 0);

                    echo '<div class="mb-3">';
                    echo '<label class="form-label">' . Html::encode($label) . '</label>';

                    if ($this->showCurrentFile && $currentId > 0) {
                        $url = call_user_func($this->fileUrlCallback, $currentId);
                        echo '<div class="d-flex align-items-center gap-2 mb-2">';
                        echo '<span class="badge bg-info">Current: #' . $currentId . '</span>';
                        echo Html::a('Open', $url, ['class' => 'btn btn-sm btn-outline-primary', 'target' => '_blank']);
                        echo '</div>';
                    }

                    if ($field->type == FormFieldType::TYPE_PICTURE) {
                        $file = ($currentId) ? File::findOne($currentId) : null;
                        echo $form->field($model, $name)->fileInput(['style' => 'display:none', 'accept' => 'image/*'])->label(false);
                        echo UploadImageInstant::widget([
                            'mode' => 'defer',
                            'model' => $model,
                            'modelId' => $field->id,
                            'attribute' => $name,
                            'fileInputId' => Html::getInputId($model, $name),
                            'imageUrl' => $file?->url ?? '',
                            'aspectRatio' => '1',
                        ]);
                    } else {
                        $inputName = "DynamicModel[{$name}]";
                        echo '<input type="file" name="' . $inputName . '" class="form-control"/>';
                    }

                    $clearId = "clear-{$name}";
                    $clearName = "DynamicModel[{$name}_clear]";
                    echo '<div class="form-check mt-2">';
                    echo '<input class="form-check-input" type="checkbox" id="' . $clearId . '" name="' . $clearName . '" value="1">';
                    echo '<label class="form-check-label" for="' . $clearId . '">Remove file</label>';
                    echo '</div>';
                    echo '</div>';
                    break;

                // ... (Outros casos mantidos)
                case FormFieldType::TYPE_MODEL:
                    $list = [];
                    if (class_exists($field->model_class) && $field->model_field) {
                        $query = $field->model_class::find();
                        if (!empty($field->model_criteria)) {
                            $query->andWhere($field->model_criteria);
                        }
                        $list = ArrayHelper::map($query->all(), 'id', $field->model_field);
                    }
                    echo $form->field($model, $name)->dropDownList($list, array_merge($options, ['prompt' => Yii::t('app', 'Select...')]));
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
                            Yii::error("SQL Field Error {$name}: " . $e->getMessage(), __METHOD__);
                        }
                    }
                    echo $form->field($model, $name)->dropDownList($list, array_merge($options, ['prompt' => Yii::t('app', 'Select...')]));
                    break;

                default:
                    echo $form->field($model, $name)->textInput($options);
                    break;
            }
        }

        if (!$this->activeForm && $this->showSave) {
            echo Html::submitButton(
                '<span class="spinner-border spinner-border-sm me-1 d-none"></span> ' . Yii::t('app', 'Save'),
                ['class' => 'btn btn-success', 'id' => 'btn-submit-dynamic-form']
            );
        }

        if (!$this->activeForm) {
            ActiveForm::end();
        }

        $output = ob_get_clean();

        // JS apenas se standalone
        if (!$this->activeForm) {
            // ... (seu código JS existente para standalone)
        }

        $this->registerInputMaskAssets();
        return $output;
    }

    protected function registerInputMaskAssets()
    {
        Yii::$app->view->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8/jquery.inputmask.min.js', [
            'depends' => [\yii\web\JqueryAsset::class],
        ]);
        Yii::$app->view->registerJs("$('.cpf-mask').inputmask({mask: ['999.999.999-99', '99.999.999/9999-99'], keepStatic: true});");
        Yii::$app->view->registerJs("$('.phone-mask').inputmask({mask: ['(99) 9999-9999', '(99) 99999-9999'], keepStatic: true});");
    }
}
