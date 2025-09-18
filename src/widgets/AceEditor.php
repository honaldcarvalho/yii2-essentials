<?php

namespace croacworks\essentials\widgets;

use Yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;
use croacworks\essentials\themes\coreui\assets\PluginAsset;
/**
 * USAGE:
 * AceEditor::widget([ 'model' => $model, 'attribute' => 'content', 'mode' => 'html', 'theme' => 'twilight', 'height' => '500px', ]);
 */
class AceEditor extends InputWidget
{
    public $theme = 'monokai';
    public $mode = 'php';
    public $height = '400px'; // altura padrão
    public $readOnly = false;
    public $clientOptions = [];

    public function init(): void
    {
        parent::init();
        PluginAsset::register($this->getView())->add(['ace']);
    }

    public function run()
    {
        $view = $this->getView();
        $id = $this->options['id'];               // ex: captive_page_append-conteudo
        $editorId = "editor_{$id}";               // div do Ace
        $inputId = $id;                           // agora SEM o prefixo input_

        $value = $this->hasModel()
            ? Html::getAttributeValue($this->model, $this->attribute)
            : $this->value;

        // Campo hidden sincronizado com Ace (id normal)
        $hiddenInput = $this->hasModel()
            ? Html::activeHiddenInput($this->model, $this->attribute, ['id' => $inputId])
            : Html::hiddenInput($this->name, $value, ['id' => $inputId]);

        // Container com altura configurável
        echo Html::beginTag('div', ['style' => "width:100%; height:{$this->height};", 'class' => 'ace-container']);
        echo Html::tag('div', '', [
            'id' => $editorId,
            'class' => 'ace_editor',
            'style' => "width:100%; height:100%; border:1px solid #ccc; border-radius:4px;"
        ]);
        echo Html::endTag('div');
        echo $hiddenInput;

        $options = Json::encode(array_merge([
            'mode' => "ace/mode/{$this->mode}",
            'theme' => "ace/theme/{$this->theme}",
            'readOnly' => $this->readOnly,
        ], $this->clientOptions));

        $var = 'ace_' . str_replace(['-', '.'], '_', $id);
        $escapedValue = json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        Yii::$app->view->registerJs(<<<JS
        onPjaxReady((root) => {
            var {$var} = ace.edit("{$editorId}", {$options});
            {$var}.session.setValue({$escapedValue});
            {$var}.session.on('change', function(){
                $("#{$inputId}").val({$var}.getValue());
            });
            {$var}.resize();

            setTimeout(() => { {$var}.resize(); }, 300);
            $('#{$editorId}').closest('.modal').on('shown.bs.modal', function () {
                {$var}.resize();
            });
        });
        JS);

    }
}
