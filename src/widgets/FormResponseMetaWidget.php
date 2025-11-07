<?php

namespace croacworks\essentials\widgets;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use croacworks\essentials\enums\FormFieldType;
use croacworks\essentials\models\FormField;
use croacworks\essentials\models\FormResponse;

/**
 * FormResponseMetaWidget
 * Renders a read-only view of FormResponse->response_data using FormField metadata.
 *
 * Usage:
 * echo FormResponseMetaWidget::widget([
 *     'formResponseId' => $metaModel->id,
 *     // OR
 *     // 'dynamicFormId' => $dynamicFormId,
 *     // 'modelClass'    => common\models\Course::class,
 *     // 'modelId'       => $model->id,
 *     'title'          => 'Course metadata',
 *     'card'           => true,
 *     // 'fileUrlCallback' => fn(int $id) => ['/storage/file/view','id'=>$id],
 * ]);
 */
class FormResponseMetaWidget extends Widget
{
    /** @var int|null */
    public ?int $formResponseId = null;

    /** @var int|null */
    public ?int $dynamicFormId = null;

    /** @var string|null FQCN of the owner model (e.g., common\models\Course) */
    public ?string $modelClass = null;

    /** @var int|null */
    public ?int $modelId = null;

    /** @var string Title shown above (if card=true) */
    public string $title = 'Metadata';

    /** @var bool Wrap output in a CoreUI/Bootstrap card */
    public bool $card = true;

    /** @var bool Show fields with empty/null values */
    public bool $showEmpty = false;

    /**
     * @var callable|null fn(int $fileId): string|array
     * Should return an URL (string) or a route array for Html::a().
     */
    public $fileUrlCallback = null;

    /** @var bool Use FormField::sort for ordering */
    public bool $orderBySort = true;

    /** @var array Extra HTML options for root container */
    public array $options = [];

    /** @var FormResponse|null */
    protected ?FormResponse $response = null;

    /** @var FormField[] indexed by name */
    protected array $fieldsByName = [];

    public function init(): void
    {
        parent::init();

        if ($this->formResponseId === null && ($this->dynamicFormId === null || $this->modelClass === null || $this->modelId === null)) {
            throw new InvalidConfigException('Provide either formResponseId OR (dynamicFormId + modelClass + modelId).');
        }

        if ($this->fileUrlCallback === null) {
            // Default fallback route (ajuste conforme seu StorageController/rotas)
            $this->fileUrlCallback = static function (int $fileId) {
                // Tente uma rota padrão de download/visualização
                return ['/file/view', 'id' => $fileId];
            };
        }

        $this->options = ArrayHelper::merge(['class' => 'cw-formresponse-meta'], $this->options);

        $this->response = $this->resolveResponse();
        $this->fieldsByName = $this->fetchFieldsIndexed($this->response->dynamic_form_id);
    }

    public function run(): string
    {
        $data = $this->decodeResponseData($this->response->response_data);

        // Ordenação por FormField::sort (se existir)
        $ordered = $this->orderDataByFields($data, $this->fieldsByName, $this->orderBySort);

        $rows = [];
        foreach ($ordered as $name => $value) {
            $field = $this->fieldsByName[$name] ?? null;

            // Skip unknown field names unless there is a non-empty value
            if (!$field && ($value === null || $value === '' || $value === [])) {
                if (!$this->showEmpty) {
                    continue;
                }
            }

            $label = $field ? ($field->label ?: $field->name) : $name;
            $type  = $field ? (int)$field->type : null;

            $formatted = $this->formatValue($type, $value, $name);
            if (!$this->showEmpty && ($formatted === '' || $formatted === null)) {
                continue;
            }

            $rows[] = [
                'label' => Html::encode($label),
                'value' => $formatted,
            ];
        }

        // Também renderize campos definidos em FormField mas ausentes no JSON (se showEmpty=true)
        if ($this->showEmpty) {
            foreach ($this->fieldsByName as $name => $field) {
                if (array_key_exists($name, $ordered)) {
                    continue;
                }
                $label = $field->label ?: $field->name;
                $rows[] = [
                    'label' => Html::encode($label),
                    'value' => '',
                ];
            }
        }

        $content = $this->renderList($rows);

        if ($this->card) {
            return Html::tag(
                'div',
                Html::tag('div', Html::tag('h5', Html::encode($this->title), ['class' => 'mb-0']), ['class' => 'card-header']) .
                    Html::tag('div', $content, ['class' => 'card-body']),
                ['class' => 'card mb-3'] + $this->options
            );
        }

        return Html::tag('div', $content, $this->options);
    }

    /** --------------------------------------------------------------------
     * Helpers
     * ------------------------------------------------------------------ */

    protected function resolveResponse(): FormResponse
    {
        if ($this->formResponseId) {
            $resp = FormResponse::findOne((int)$this->formResponseId);
            if (!$resp) {
                throw new InvalidConfigException('FormResponse not found: id=' . $this->formResponseId);
            }
            return $resp;
        }

        // Resolver por (dynamic_form_id + modelClass/modelId) suportando colunas variadas
        $schema = Yii::$app->db->schema->getTableSchema(FormResponse::tableName(), true);
        $cols   = $schema ? $schema->columns : [];

        $where = ['dynamic_form_id' => (int)$this->dynamicFormId];

        if (isset($cols['model_class'], $cols['model_id'])) {
            $where['model_class'] = $this->modelClass;
            $where['model_id']    = (int)$this->modelId;
        } elseif (isset($cols['owner_class'], $cols['owner_id'])) {
            $where['owner_class'] = $this->modelClass;
            $where['owner_id']    = (int)$this->modelId;
        } elseif (isset($cols['page_id'])) {
            $where['page_id']     = (int)$this->modelId;
        } elseif (isset($cols['model'], $cols['model_pk'])) {
            $where['model']       = $this->modelClass;
            $where['model_pk']    = (int)$this->modelId;
        }

        $resp = FormResponse::find()->where($where)->one();
        if (!$resp) {
            throw new InvalidConfigException('FormResponse not found for given criteria.');
        }
        return $resp;
    }

    /**
     * @return FormField[] indexed by name
     */
    protected function fetchFieldsIndexed(int $dynamicFormId): array
    {
        $schema = Yii::$app->db->schema->getTableSchema(\croacworks\essentials\models\FormField::tableName(), true);
        $cols   = $schema ? array_keys($schema->columns) : [];

        // candidatos comuns para ordenar
        $candidates = ['sort', 'position', 'sort_order', 'ord', 'order', 'weight'];

        // pega o primeiro que existir na tabela; senão cai para id
        $orderCol = null;
        foreach ($candidates as $c) {
            if (in_array($c, $cols, true)) {
                $orderCol = $c;
                break;
            }
        }

        $q = \croacworks\essentials\models\FormField::find()
            ->where(['dynamic_form_id' => (int)$dynamicFormId]);

        if ($orderCol !== null) {
            $q->orderBy([$orderCol => SORT_ASC, 'id' => SORT_ASC]);
        } else {
            $q->orderBy(['id' => SORT_ASC]);
        }

        /** @var \croacworks\essentials\models\FormField[] $list */
        $list = $q->all();

        $byName = [];
        foreach ($list as $f) {
            $byName[$f->name] = $f;
        }
        return $byName;
    }

    protected function decodeResponseData($raw): array
    {
        if (is_array($raw)) return $raw;
        if (is_string($raw)) {
            $d = json_decode($raw, true);
            return is_array($d) ? $d : [];
        }
        return [];
    }

    protected function orderDataByFields(array $data, array $fieldsByName, bool $useSort): array
    {
        if (!$useSort || empty($fieldsByName)) {
            return $data;
        }

        $weights = [];
        $i = 0;
        foreach ($fieldsByName as $name => $field) {
            $weights[$name] = [$field->sort ?? 999999, $i++];
        }

        uksort($data, function ($a, $b) use ($weights) {
            $wa = $weights[$a] ?? [999999, PHP_INT_MAX];
            $wb = $weights[$b] ?? [999999, PHP_INT_MAX];
            return $wa <=> $wb;
        });

        return $data;
    }

    protected function formatValue(?int $type, $value, string $name): string
    {
        $fmt = Yii::$app->formatter;

        // Null / vazio
        if ($value === null || $value === '') {
            return '';
        }

        // Matriz de valores (ex.: multiple/checkbox)
        if (is_array($value) && $type !== FormFieldType::TYPE_FILE) {
            // Tente representar como badges
            $badges = array_map(fn($v) => Html::tag('span', Html::encode((string)$v), ['class' => 'badge bg-secondary me-1']), $value);
            return implode(' ', $badges);
        }

        switch ($type) {
            case FormFieldType::TYPE_NUMBER:
                return Html::encode((string)$value);

            case FormFieldType::TYPE_DATE:
                return Html::encode($fmt->asDate($value));

            case FormFieldType::TYPE_DATETIME:
                return Html::encode($fmt->asDatetime($value));

            case FormFieldType::TYPE_EMAIL:
                return Html::a(Html::encode((string)$value), 'mailto:' . $value);

            case FormFieldType::TYPE_PHONE:
                return Html::a(Html::encode((string)$value), 'tel:' . preg_replace('/\D+/', '', (string)$value));

            case FormFieldType::TYPE_FILE:
                // Espera-se que o value seja um file_id (int/string)
                $fileId = (int)$value;
                if ($fileId <= 0) return '';
                $url = call_user_func($this->fileUrlCallback, $fileId);
                return Html::a(Yii::t('app', 'Open file #{id}', ['id' => $fileId]), $url, ['target' => '_blank', 'rel' => 'noopener']);

            case FormFieldType::TYPE_TEXT:
            case FormFieldType::TYPE_TEXTAREA:
            case FormFieldType::TYPE_SELECT:
            case FormFieldType::TYPE_SQL:
            case FormFieldType::TYPE_MODEL:
            case FormFieldType::TYPE_IDENTIFIER:
            default:
                return Html::encode(is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE));
        }
    }

    protected function renderList(array $rows): string
    {
        if (empty($rows)) {
            return Html::tag('div', Html::encode(Yii::t('app', 'No metadata.')), ['class' => 'text-muted']);
        }

        // Definition list responsivo (duas colunas)
        $html = Html::beginTag('div', ['class' => 'row row-cols-1 row-cols-md-2 g-3']);
        foreach ($rows as $r) {
            $html .= Html::tag(
                'div',
                Html::tag(
                    'div',
                    Html::tag('div', $r['label'], ['class' => 'text-muted small']) .
                        Html::tag('div', $r['value'] === '' ? Html::tag('span', Yii::t('app', '(empty)'), ['class' => 'text-muted']) : $r['value'], ['class' => 'fw-semibold']),
                    ['class' => 'p-3 border rounded-3 h-100']
                ),
                ['class' => 'col']
            );
        }
        $html .= Html::endTag('div');
        return $html;
    }
}
