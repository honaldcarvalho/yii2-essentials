<?php
namespace croacworks\essentials\widgets\form;

use Yii;
use yii\helpers\Html;
use yii\widgets\ActiveField as BaseActiveField;

class SearchActiveField extends BaseActiveField
{
    /**
     * Renderiza campos de busca “compostos”.
     *
     * Exemplos:
     * 
        use croacworks\essentials\widgets\form\SearchActiveField;
        use yii\widgets\ActiveForm;

        $form = ActiveForm::begin([
            'method'     => 'get',
            'fieldClass' => SearchActiveField::class,
        ]);

        echo $form->field($model, 'created_at')->search('date', '>=');

        echo $form->field($model, 'created_at')->search('date', '>=', [
            'label' => Yii::t('app', 'Created At'),
            'startOptions' => ['placeholder' => Yii::t('app','Start date')],
            'endOptions'   => ['placeholder' => Yii::t('app','End date')],
        ]);

        ActiveForm::end();
     * @param string $type      Tipos suportados: 'date'
     * @param string $operator  Mantido por compat (ex.: '>='); para 'date' sempre usa >= início e <= fim
     * @param array  $options   [
     *   'startOptions' => [], // htmlOptions do input inicial
     *   'endOptions'   => [], // htmlOptions do input final
     *   'template'     => "{label}\n{input}\n{hint}\n{error}",
     *   'label'        => 'Criado em',
     * ]
     * @return static
     */
    public function search(string $type = 'date', string $operator = '>=', array $options = [])
    {
        $attr = $this->attribute;

        if ($type === 'date') {
            // Atributos especiais já previstos no ModelCommon (safe)
            $startAttr = "{$attr}FDTsod"; // >=
            $endAttr   = "{$attr}FDTeod"; // <=

            $common = ['class' => 'form-control', 'type' => 'date'];
            $startOptions = array_merge($common, $options['startOptions'] ?? []);
            $endOptions   = array_merge($common, $options['endOptions'] ?? []);

            $startInput = Html::activeInput('date', $this->model, $startAttr, $startOptions);
            $endInput   = Html::activeInput('date', $this->model, $endAttr, $endOptions);

            // Layout simples: dois colunas lado a lado (usa seu CSS / CoreUI/Bootstrap)
            $content =
                Html::tag('div',
                    Html::tag('div', $startInput, ['class' => 'col']) .
                    Html::tag('div', $endInput,   ['class' => 'col']),
                    ['class' => 'row g-2']
                );

            $this->parts['{input}'] = $content;

            // Label padrão “Created At (De/Até)”, personalizável por $options['label']
            $labelText = $options['label'] ?? ($this->model->getAttributeLabel($attr) . ' (' . Yii::t('app', 'From') . '/' . Yii::t('app', 'To') . ')');
            $this->label($labelText);

            $this->template = $options['template'] ?? "{label}\n{input}\n{hint}\n{error}";

            return $this;
        }

        // Tipos futuros podem ser adicionados aqui (ex.: 'number', 'money', etc.)
        return $this;
    }
}
