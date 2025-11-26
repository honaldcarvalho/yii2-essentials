<?php

namespace croacworks\essentials\components\gridview;

use Yii;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use croacworks\essentials\models\DynamicForm;
use croacworks\essentials\models\FormField;
use croacworks\essentials\enums\FormFieldType;

/**
 * GridView specialized for FormResponse listing.
 * - Auto builds DataColumns from DynamicForm->formFields
 * - Resolves Relations (Model) and Static Options (Select/Multiple)
 */

class FormResponseGridView extends GridView
{
    /** @var DynamicForm */
    public DynamicForm $dynamicForm;
    public $controller = 'form-response-crud';

    /** @var string[]|null */
    public ?array $visibleFields = null;

    /** @var int|null */
    public ?int $limit = null;

    /** @var bool */
    public bool $withSystemColumns = true;

    /** @var array */
    public array $cellOptions = [];

    public function init()
    {
        if (!isset($this->dynamicForm)) {
            throw new \InvalidArgumentException('FormResponseGridView requires $dynamicForm.');
        }
        if (empty($this->columns)) {
            $this->columns = $this->buildColumns();
        }
        $this->tableOptions = $this->tableOptions ?: ['class' => 'table table-striped table-bordered align-middle'];
        parent::init();
    }

    protected function buildColumns(): array
    {
        $cols = [];

        if ($this->withSystemColumns) {
            $cols[] = ['class' => 'yii\grid\SerialColumn'];
            $cols[] = [
                'attribute' => 'id',
                'contentOptions' => ['style' => 'width:100px; white-space:nowrap;']
            ];
        }

        $fields = $this->dynamicForm->formFields;
        $fields = array_values(array_filter($fields, fn(FormField $f) => true));

        if ($this->visibleFields) {
            $map = [];
            foreach ($fields as $f) $map[$f->name] = $f;
            $ordered = [];
            foreach ($this->visibleFields as $name) {
                if (isset($map[$name])) $ordered[] = $map[$name];
            }
            $fields = $ordered;
        }

        if ($this->limit !== null) {
            $fields = array_slice($fields, 0, (int)$this->limit);
        }

        foreach ($fields as $field) {
            $columnConfig = [
                'class' => FormResponseFieldColumn::class,
                'field' => $field,
            ];

            // 1. Handle External Models (TYPE_MODEL)
            if ($field->type == FormFieldType::TYPE_MODEL && !empty($field->model_class) && !empty($field->model_field)) {
                $columnConfig['value'] = function ($model) use ($field) {
                    $id = ArrayHelper::getValue($model, $field->name);
                    if (empty($id)) return null;

                    $relatedClass = $field->model_class;
                    if (class_exists($relatedClass)) {
                        /** @var \yii\db\ActiveRecord $relatedModel */
                        $relatedModel = $relatedClass::findOne($id);
                        if ($relatedModel) {
                            return $relatedModel->{$field->model_field};
                        }
                    }
                    return $id;
                };
            }
            // 2. Handle Static Options (TYPE_SELECT or TYPE_MULTIPLE)
            elseif (in_array($field->type, [FormFieldType::TYPE_SELECT, FormFieldType::TYPE_MULTIPLE])) {

                // Parse items string: "KEY:Label;KEY2:Label2"
                $options = [];
                if (!empty($field->items)) {
                    $rawItems = explode(';', $field->items);
                    foreach ($rawItems as $item) {
                        $parts = explode(':', $item);
                        if (count($parts) >= 2) {
                            // Trim to remove potential accidental spaces
                            $key = trim($parts[0]);
                            $label = trim($parts[1]);
                            $options[$key] = $label;
                        }
                    }
                }

                $columnConfig['value'] = function ($model) use ($field, $options) {
                    $value = ArrayHelper::getValue($model, $field->name);

                    if ($value === null || $value === '') {
                        return null;
                    }

                    // If Multiple Select
                    if ($field->type == FormFieldType::TYPE_MULTIPLE) {
                        // Decode if JSON, otherwise force array
                        $values = is_string($value) ? json_decode($value, true) : $value;
                        if (!is_array($values)) {
                            $values = [$value];
                        }

                        $labels = [];
                        foreach ($values as $v) {
                            $labels[] = $options[$v] ?? $v;
                        }
                        return implode(', ', $labels);
                    }

                    // If Single Select
                    return $options[$value] ?? $value;
                };
            }

            $cols[] = array_merge($columnConfig, $this->cellOptions);
        }

        if ($this->withSystemColumns) {
            $cols[] = [
                'attribute' => 'created_at',
                'format' => ['datetime', 'php:Y-m-d H:i'],
                'contentOptions' => ['style' => 'white-space:nowrap;']
            ];
            $cols[] = [
                'class' => 'croacworks\essentials\components\gridview\ActionColumnCustom',
                'controller' => $this->controller,
            ];
        }

        return $cols;
    }
}
