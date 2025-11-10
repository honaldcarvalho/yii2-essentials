<?php

namespace croacworks\essentials\components\gridview;

use Yii;
use yii\grid\DataColumn;
use yii\helpers\Html;
use yii\helpers\Url;
use croacworks\essentials\enums\FormFieldType;
use croacworks\essentials\models\File;
use croacworks\essentials\models\FormField;
use croacworks\essentials\models\FormResponse;

class FormResponseFieldColumn extends DataColumn
{
    /** @var FormField Campo de definição (label, name, type, items etc.) */
    public FormField $field;

    /** @var bool Render empty values? */
    public bool $showEmpty = false;

    /** @var array Thumb params for images/files */
    public array $thumb = ['w' => 64, 'h' => 64, 'fit' => 'cover'];

    public function init()
    {
        parent::init();
        if (!isset($this->field)) {
            throw new \InvalidArgumentException('FormResponseFieldColumn requires $field.');
        }
        $this->label = $this->label ?: ($this->field->label ?: $this->field->name);
        // Compute value using a closure so filters/sorts still work for other columns
        $name  = $this->field->name;
        $type  = (int)$this->field->type;
        $this->value = function (FormResponse $model) use ($name, $type) {
            $raw = $model->getFieldValue($name);
            return $this->formatByType($type, $raw, $name, $model);
        };
        // Most cells output raw HTML (badges, links, imgs)
        $this->format = 'raw';
        $this->contentOptions = $this->contentOptions ?: ['style' => 'vertical-align:middle'];
    }

    /**
     * Type-aware formatter.
     */
    protected function formatByType(int $type, $value, string $name, FormResponse $model): string
    {
        // Normalize JSON strings -> arrays
        if (is_string($value)) {
            $trim = trim($value);
            if (($trim && ($trim[0] === '{' || $trim[0] === '['))) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $value = $decoded;
                }
            }
        }

        // Handle empty visibility
        if (!$this->showEmpty && ($value === null || $value === '')) {
            return '';
        }

        switch ($type) {
            case FormFieldType::TYPE_HIDDEN:
                return '<span class="text-muted">—</span>';

            case FormFieldType::TYPE_TEXT:
            case FormFieldType::TYPE_TEXTAREA:
            case FormFieldType::TYPE_IDENTIFIER:
                return Html::encode((string)$value);

            case FormFieldType::TYPE_NUMBER:
                return Yii::$app->formatter->asDecimal((float)$value);

            case FormFieldType::TYPE_DATE:
                return $value ? Yii::$app->formatter->asDate($value) : '';

            case FormFieldType::TYPE_DATETIME ?? 99901: // fallback if you have a datetime type
                return $value ? Yii::$app->formatter->asDatetime($value) : '';

            case FormFieldType::TYPE_EMAIL ?? 99902:
                return $value ? Html::a(Html::encode($value), 'mailto:' . $value) : '';

            case FormFieldType::TYPE_PHONE ?? 99903:
                if (!$value) return '';
                $plain = preg_replace('/\D+/', '', (string)$value);
                return Html::a(Html::encode((string)$value), 'tel:' . $plain);

            // case FormFieldType::TYPE_LINK ?? 99904:
            //     if (!$value) return '';
            //     $label = is_array($value) ? ($value['label'] ?? ($value['url'] ?? 'link')) : (string)$value;
            //     $url   = is_array($value) ? ($value['url'] ?? $label) : (string)$value;
            //     return Html::a(Html::encode($label), $url, ['target' => '_blank', 'rel' => 'noopener']);

            case FormFieldType::TYPE_CHECKBOX:
                $ok = (bool)$value;
                $class = $ok ? 'badge bg-success' : 'badge bg-secondary';
                $text  = $ok ? Yii::t('app', 'Yes') : Yii::t('app', 'No');
                return Html::tag('span', Html::encode($text), ['class' => $class]);

            case FormFieldType::TYPE_SELECT:
                // single select: value may be "key" or {"value":"key","label":"..."}
                if (is_array($value) && isset($value['label'])) {
                    return Html::tag('span', Html::encode($value['label']), ['class' => 'badge bg-primary']);
                }
                return Html::tag('span', Html::encode((string)$value), ['class' => 'badge bg-primary']);

            case FormFieldType::TYPE_MULTIPLE:
                // multi-select: array of values/labels
                $items = [];
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $label = is_array($v) ? ($v['label'] ?? ($v['value'] ?? '')) : (string)$v;
                        if ($label === '') continue;
                        $items[] = Html::tag('span', Html::encode($label), ['class' => 'badge bg-info me-1']);
                    }
                }
                return $items ? implode(' ', $items) : '';

            case FormFieldType::TYPE_FILE:
                return $this->renderFile($value);

            case FormFieldType::TYPE_PICTURE:
                return $this->renderPicture($value);

            case FormFieldType::TYPE_MODEL ?? 99905:
                // could be id or {id,label}
                if (is_array($value)) {
                    $label = $value['label'] ?? ($value['id'] ?? '');
                    return Html::encode((string)$label);
                }
                return Html::encode((string)$value);

            case FormFieldType::TYPE_SQL ?? 99906:
                // free text or object -> JSON preview
                return $this->renderJsonPreview($value);

            default:
                // Fallback: encode strings, JSON-preview for arrays/objects
                if (is_array($value) || is_object($value)) {
                    return $this->renderJsonPreview($value);
                }
                return Html::encode((string)$value);
        }
    }

    protected function renderJsonPreview($value): string
    {
        if ($value === null || $value === '') return '';
        $json = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        if ($json === false) $json = (string)$value;
        return Html::tag('pre', Html::encode($json), [
            'class' => 'mb-0 small',
            'style' => 'max-width:520px; white-space:pre-wrap; word-break:break-word;'
        ]);
    }

    /**
     * Accepts:
     *  - int file_id
     *  - {"id":123,"name":"file.pdf"} or list of these
     */
    protected function renderFile($value): string
    {
        $files = $this->normalizeFileList($value);
        if (!$files) return '';

        $out = [];
        foreach ($files as $f) {
            $id   = (int)$f['id'];
            $name = (string)($f['name'] ?? ('#' . $id));
            $url  = Url::to(['/storage/get', 'id' => $id]);
            $out[] = Html::a(Html::encode($name), $url, ['target' => '_blank', 'rel' => 'noopener']);
        }
        return implode('<br>', $out);
    }

    /**
     * Accepts:
     *  - int file_id (image)
     *  - {"id":123,"name":"img.png"} or list of these
     */
    protected function renderPicture($value): string
    {
        $files = $this->normalizeFileList($value);
        if (!$files) return '';

        $thumbs = [];
        foreach ($files as $f) {
            $id = (int)$f['id'];

            if($id){
                $file = File::findOne($id);
                $url = $file?->urlThumb;
            } else {
                return Yii::t('app','No image selected');
            }
            $thumbs[] = Html::a(
                Html::img($url, ['alt' => $f['name'] ?? ('#'.$id), 'class' => 'img-thumbnail me-1','width'=>'50']),
                $url,
                [
                    'target' => '_blank', 
                    'rel' => 'noopener',
                    'class' => 'btn btn-outline-secondary',
                    'data-fancybox' => "",
                    'data-type' => "image",
                    'title' => \Yii::t('app', 'View')
                ]
            );
        }
        return implode('', $thumbs);
    }

    /**
     * Normalizes many possible shapes into a list: [['id'=>..,'name'=>..], ...]
     */
    protected function normalizeFileList($value): array
    {
        if ($value === null || $value === '') return [];

        // If numeric -> single id
        if (is_scalar($value) && preg_match('/^\d+$/', (string)$value)) {
            return [['id' => (int)$value]];
        }

        // If JSON string, decode
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            } else {
                return [];
            }
        }

        // Single object -> wrap
        if (is_array($value) && isset($value['id'])) {
            return [['id' => (int)$value['id'], 'name' => $value['name'] ?? null]];
        }

        // List of ids or objects
        $list = [];
        if (is_array($value)) {
            foreach ($value as $v) {
                if (is_array($v) && isset($v['id'])) {
                    $list[] = ['id' => (int)$v['id'], 'name' => $v['name'] ?? null];
                } elseif (is_scalar($v) && preg_match('/^\d+$/', (string)$v)) {
                    $list[] = ['id' => (int)$v];
                }
            }
        }
        return $list;
    }
}
