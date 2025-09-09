<?php

namespace croacworks\essentials\widgets\form;

use Yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;
use croacworks\essentials\themes\adminlte3\assets\PluginAsset;

class TinyMCE extends InputWidget
{
    public $baseUrl;
    public $language;
    public $clientOptions = [];

    public function init(): void
    {
        parent::init();
        $view = $this->getView();
        $pluginAssets = PluginAsset::register($view)->add(['tinymce']);
        $this->baseUrl = $pluginAssets->baseUrl;
        $textBig = <<< HTML
        <h3>The standard Lorem Ipsum passage, used since the 1500s</h3>
        <p>"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum."</p>
        <h3>Section 1.10.32 of "de Finibus Bonorum et Malorum", written by Cicero in 45 BC</h3>
        <p>"Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?"</p>
        <h3>1914 translation by H. Rackham</h3>
        <p>"But I must explain to you how all this mistaken idea of denouncing pleasure and praising pain was born and I will give you a complete account of the system, and expound the actual teachings of the great explorer of the truth, the master-builder of human happiness. No one rejects, dislikes, or avoids pleasure itself, because it is pleasure, but because those who do not know how to pursue pleasure rationally encounter consequences that are extremely painful. Nor again is there anyone who loves or pursues or desires to obtain pain of itself, because it is pain, but because occasionally circumstances occur in which toil and pain can procure him some great pleasure. To take a trivial example, which of us ever undertakes laborious physical exercise, except to obtain some advantage from it? But who has any right to find fault with a man who chooses to enjoy a pleasure that has no annoying consequences, or one who avoids a pain that produces no resultant pleasure?"</p>
        <h3>Section 1.10.33 of "de Finibus Bonorum et Malorum", written by Cicero in 45 BC</h3>
        <p>"At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus. Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Itaque earum rerum hic tenetur a sapiente delectus, ut aut reiciendis voluptatibus maiores alias consequatur aut perferendis doloribus asperiores repellat."</p>
        HTML;
        // Adicionando o botão padrão "Lorem Ipsum" na configuração
        $this->clientOptions = array_merge([
            'plugins' => [
                'advlist', 'autolink', 'link', 'image', 'lists', 'charmap', 'preview', 'anchor', 'pagebreak',
                'searchreplace', 'wordcount', 'visualblocks', 'code', 'fullscreen', 'insertdatetime', 'media',
                'table', 'emoticons', 'template', 'help'
            ],
            'toolbar' => "undo redo | styles | bold italic | alignleft aligncenter alignright alignjustify | " .
                         "bullist numlist outdent indent | link image loremIpsumSmall loremIpsumBig| print preview media fullscreen | " .
                         "forecolor backcolor emoticons ",
            'setup' => new \yii\web\JsExpression('function(editor) {
                editor.ui.registry.addButton("loremIpsumSmall", {
                    text: "Lorem Small",
                    icon: "edit-block",
                    tooltip: "Inserir texto Lorem Ipsum",
                    onAction: function () {
                        let loremText = "Lorem ipsum dolor sit amet, consectetur adipiscing elit.";
                        editor.insertContent(loremText);
                    }
                });
                editor.ui.registry.addButton("loremIpsumBig", {
                    text: "Lorem Big",
                    icon: "edit-block",
                    tooltip: "Inserir texto Lorem Ipsum",
                    onAction: function () {
                        let loremText = `'.$textBig.'`;
                        editor.insertContent(loremText);
                    }
                });
            }')
        ], $this->clientOptions);
    }

    public function run()
    {
        $js = [];
        $view = $this->getView();
        $id = $this->options['id'];

        $this->clientOptions['selector'] = "#$id";

        if ($this->language !== null && $this->language !== 'en-US') {
            $this->clientOptions['language'] = strtolower(str_replace('-', '_', $this->language));
        }

        $options = Json::encode($this->clientOptions);

        if ($this->hasModel()) {
            echo Html::activeTextarea($this->model, $this->attribute, $this->options);
        } else {
            echo Html::textarea($this->name, $this->value, $this->options);
        }

        $js[] = "tinymce.remove('#$id'); tinymce.init($options);";
        $view->registerJs(implode("\n", $js));
    }
}
