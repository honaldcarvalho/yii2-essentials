<?php

namespace croacworks\essentials\widgets;

use Yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;
use yii\widgets\InputWidget;
use croacworks\essentials\themes\adminlte3\assets\PluginAsset;

class Ckeditor extends InputWidget
{
    public $language;
    public $clientOptions = [];

    public function init(): void
    {
        parent::init();

        $view = $this->getView();
        PluginAsset::register($view)->add(['ckeditor']);

        $textBig = <<<HTML
        <h3>The standard Lorem Ipsum passage, used since the 1500s</h3>
        <p>"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua..."</p>
        <h3>Section 1.10.32 of "de Finibus Bonorum et Malorum"</h3>
        <p>"Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium..."</p>
        HTML;

        $this->clientOptions = array_merge([
            'toolbar' => [
                'heading',
                '|',
                'bold',
                'italic',
                'link',
                'bulletedList',
                'numberedList',
                '|',
                'insertTable',
                'blockQuote',
                'undo',
                'redo',
                '|',
                'loremIpsumSmall',
                'loremIpsumBig'
            ]
        ], $this->clientOptions);

        $view->registerJs(<<<JS

        const {
            ClassicEditor,
            Essentials,
            Paragraph,
            Bold,
            Italic,
            Font
        } = CKEDITOR;
        // Create a free account and get <YOUR_LICENSE_KEY>
        // https://portal.ckeditor.com/checkout?plan=free
        ClassicEditor
            .create( document.querySelector( '#editor' ), {
                licenseKey: 'eyJhbGciOiJFUzI1NiJ9.eyJleHAiOjE3NDcyNjcxOTksImp0aSI6ImYyN2VlNmY5LWM1YjctNGQ0Ni1hOTM5LTMyYTk2N2VkYmE4OCIsInVzYWdlRW5kcG9pbnQiOiJodHRwczovL3Byb3h5LWV2ZW50LmNrZWRpdG9yLmNvbSIsImRpc3RyaWJ1dGlvbkNoYW5uZWwiOlsiY2xvdWQiLCJkcnVwYWwiLCJzaCJdLCJ3aGl0ZUxhYmVsIjp0cnVlLCJsaWNlbnNlVHlwZSI6InRyaWFsIiwiZmVhdHVyZXMiOlsiKiJdLCJ2YyI6IjY3N2JjNzJlIn0.23WYLTxI7SCw2u5J4euMc3CH5QviWdGMY_tsMdXjr2w1cNRmpf64WT9grvBlZ8utkDu7wdUJHrU6sO3dtnzQjg',
                plugins: [ Essentials, Paragraph, Bold, Italic, Font ],
                toolbar: [
                    'undo', 'redo', '|', 'bold', 'italic', '|',
                    'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor'
                ]
            } )
            .then( editor => {
                window.editor = editor;
            } )
            .catch( error => {
                console.error( error );
            } );
        JS, View::POS_HEAD);
    }

    public function run(): void
    {
        $view = $this->getView();
        $id = $this->options['id'];

        if ($this->hasModel()) {
            echo Html::activeTextarea($this->model, $this->attribute, $this->options);
        } else {
            echo Html::textarea($this->name, $this->value, $this->options);
        }

        $options = Json::encode(array_merge($this->clientOptions, [
            'extraPlugins' => ['LoremIpsumSmallPlugin', 'LoremIpsumBigPlugin'],
        ]));

        $view->registerJs(<<<JS
            if (window.ClassicEditor) {
                ClassicEditor
                    .create(document.querySelector('#{$id}'), {$options})
                    .catch(error => console.error(error));
            }
        JS);
    }
}
