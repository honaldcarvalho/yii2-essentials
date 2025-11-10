<?php
namespace croacworks\essentials\grid;

use Yii;
use yii\grid\GridView;
use croacworks\essentials\models\DynamicForm;
use croacworks\essentials\models\FormField;

/**
 * GridView specialized for FormResponse listing.
 * - Auto builds DataColumns from DynamicForm->formFields
 * - You may choose which fields to show
 */
class FormResponseGridView extends GridView
{
    /** @var DynamicForm */
    public DynamicForm $dynamicForm;

    /**
     * @var string[]|null Field names to include (order respected).
     *                    If null, shows all visible fields.
     */
    public ?array $visibleFields = null;

    /** @var int|null Limit to first N fields (applied after visibleFields, if set). */
    public ?int $limit = null;

    /** @var bool Include default ID + created_at + action column */
    public bool $withSystemColumns = true;

    /** @var array Options forwarded to FormResponseFieldColumn (e.g., ['thumb'=>['w'=>96,'h'=>96]]) */
    public array $cellOptions = [];

    public function init()
    {
        if (!isset($this->dynamicForm)) {
            throw new \InvalidArgumentException('FormResponseGridView requires $dynamicForm.');
        }
        if (empty($this->columns)) {
            $this->columns = $this->buildColumns();
        }
        // Default table classes
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

        // Choose which fields to show
        $fields = $this->dynamicForm->formFields;
        $fields = array_values(array_filter($fields, fn(FormField $f) => true)); // here you can filter by "show" if your schema has it

        if ($this->visibleFields) {
            // keep order as provided
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
            $cols[] = array_merge([
                'class' => FormResponseFieldColumn::class,
                'field' => $field,
            ], $this->cellOptions);
        }

        if ($this->withSystemColumns) {
            $cols[] = [
                'attribute' => 'created_at',
                'format' => ['datetime', 'php:Y-m-d H:i'],
                'contentOptions' => ['style' => 'white-space:nowrap;']
            ];
            $cols[] = [
                'class' => 'yii\grid\ActionColumn',
                'controller' => 'form-response-crud',
            ];
        }

        return $cols;
    }
}
