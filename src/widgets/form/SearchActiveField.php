<?php
namespace croacworks\essentials\widgets\form;

use Yii;
use yii\bootstrap5\Html;
use yii\helpers\Inflector;
use yii\bootstrap5\ActiveField as BaseActiveField;

class SearchActiveField extends BaseActiveField
{
    /**
     * Campo de busca composto.
     * Ex.: $form->field($m, 'created_at')->search('date', '>=');
     *
     * - Se o model tiver os atributos virtuais (ex.: created_atFDTsod / created_atFDTeod),
     *   usa inputs "ativos" (Html::activeInput).
     * - Se NÃO tiver, usa inputs "não-ativos" com name manual (fallback dinâmico),
     *   evitando UnknownPropertyException.
     */
    public function search(string $type = 'date', string $operator = '>=', array $options = [])
    {
        $attr = $this->attribute;

        if ($type !== 'date') {
            return $this; // (pode-se expandir p/ outros tipos depois)
        }

        $startAttr = "{$attr}FDTsod"; // >=
        $endAttr   = "{$attr}FDTeod"; // <=

        $common = ['class' => 'form-control', 'type' => 'date'];
        $startOptions = array_merge($common, $options['startOptions'] ?? []);
        $endOptions   = array_merge($common, $options['endOptions'] ?? []);

        // Checa se o model expõe os atributos especiais (get/set ou public)
        $hasStart = $this->model->canGetProperty($startAttr, true) || $this->model->canSetProperty($startAttr, true) || property_exists($this->model, $startAttr);
        $hasEnd   = $this->model->canGetProperty($endAttr, true)   || $this->model->canSetProperty($endAttr, true)   || property_exists($this->model, $endAttr);

        // Captura valores atuais do request (útil no modo não-ativo e para manter valor após submit)
        $formName = $this->model->formName();
        $req      = Yii::$app->request->get($formName, Yii::$app->request->post($formName, []));
        $startVal = $req[$startAttr] ?? null;
        $endVal   = $req[$endAttr] ?? null;

        if ($hasStart && $hasEnd) {
            // ====== MODO "ATIVO" (bound ao model) ======
            $startInput = Html::activeInput('date', $this->model, $startAttr, $startOptions);
            $endInput   = Html::activeInput('date', $this->model, $endAttr, $endOptions);
        } else {
            // ====== MODO "DINÂMICO" (sem attributes no model) ======
            // name/id manuais no formato <Model>[created_atFDTsod]
            $startName = "{$formName}[{$startAttr}]";
            $endName   = "{$formName}[{$endAttr}]";

            $startId = strtolower($formName . '-' . Inflector::slug($startAttr));
            $endId   = strtolower($formName . '-' . Inflector::slug($endAttr));

            $startInput = Html::input('date', $startName, $startVal, array_merge(['id' => $startId], $startOptions));
            $endInput   = Html::input('date', $endName,   $endVal,   array_merge(['id' => $endId],   $endOptions));
        }

        $content = Html::tag('div',
            Html::tag('div', $startInput, ['class' => 'col']) .
            Html::tag('div', $endInput,   ['class' => 'col']),
            ['class' => 'row g-2']
        );

        $this->parts['{input}'] = $content;

        $labelText = $options['label'] ?? ($this->model->getAttributeLabel($attr) . ' (' . Yii::t('app', 'From') . '/' . Yii::t('app', 'To') . ')');
        $this->label($labelText);

        $this->template = $options['template'] ?? "{label}\n{input}\n{hint}\n{error}";

        return $this;
    }
}
