<?php

namespace croacworks\essentials\widgets;

use Yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;
use croacworks\essentials\themes\coreui\assets\PluginAsset;

/**
 * TinyMCE Widget
 * --------------
 * 
 * A reusable, CoreUI-compatible TinyMCE integration for Yii2.
 *
 * This widget provides a rich-text editor with full HTML freedom and
 * special protection for Mustache/Handlebars-style placeholders such as:
 * 
 *     {{#each items}} ... {{/each}}
 *     {patient_name}, {total}, etc.
 *
 * It is particularly designed for editable HTML templates used in
 * report systems, allowing dynamic placeholders to remain intact.
 *
 * ## Features
 * - Fully integrated with Yii2 ActiveForm and InputWidget
 * - Preserves template placeholders (e.g., `{{#each}}` and `{field}`)
 * - Disables TinyMCE’s automatic HTML correction
 * - Allows all HTML elements and attributes
 * - Provides two "Lorem Ipsum" buttons for quick dummy text insertion
 * - Works seamlessly with the CoreUI theme asset pipeline
 *
 * ## Example Usage
 * 
 * ```php
 * use croacworks\essentials\widgets\TinyMCE;
 * 
 * echo $form->field($model, 'body_html')->widget(TinyMCE::class, [
 *     'language' => 'en',
 *     'clientOptions' => [
 *         'height' => 400,
 *     ],
 * ]);
 * ```
 *
 * ## Example Template (Preserved Intact)
 * ```html
 * <table border="1" width="100%">
 *   <thead>
 *     <tr><th>Item</th><th>Value</th></tr>
 *   </thead>
 *   <tbody>
 *     {{#each items}}
 *     <tr>
 *       <td>{item_name}</td>
 *       <td>{item_value}</td>
 *     </tr>
 *     {{/each}}
 *   </tbody>
 * </table>
 * ```
 */
class TinyMCE extends InputWidget
{
    /**
     * @var string|null Base URL for TinyMCE assets
     */
    public $baseUrl;

    /**
     * @var string|null Editor language code (e.g., 'en', 'pt_br')
     */
    public $language;

    /**
     * @var array TinyMCE configuration options merged with defaults
     */
    public $clientOptions = [];

    /**
     * Initializes the widget.
     *
     * Registers TinyMCE assets, merges default configuration, 
     * and defines custom toolbar buttons.
     *
     * Includes:
     * - Mustache tag protection
     * - Raw entity encoding
     * - Disabled HTML cleanup and verification
     *
     * @return void
     */
    public function init(): void
    {
        parent::init();
        $view = $this->getView();

        // Register TinyMCE plugin asset
        $pluginAssets = PluginAsset::register($view)->add(['tinymce']);
        $this->baseUrl = $pluginAssets->baseUrl;

        // Example long Lorem Ipsum content used by toolbar button
        $textBig = <<< HTML
        <h3>The standard Lorem Ipsum passage, used since the 1500s</h3>
        <p>"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua..."</p>
        HTML;

        // Merge default TinyMCE options with any custom ones provided
        $this->clientOptions = array_merge([
            'plugins' => [
                'advlist', 'autolink', 'link', 'image', 'lists', 'charmap', 'preview', 'anchor', 'pagebreak',
                'searchreplace', 'wordcount', 'visualblocks', 'code', 'fullscreen', 'insertdatetime', 'media',
                'table', 'emoticons', 'template', 'help'
            ],
            'toolbar' => "undo redo | styles | bold italic | alignleft aligncenter alignright alignjustify | " .
                         "bullist numlist outdent indent | link image loremIpsumSmall loremIpsumBig | " .
                         "print preview media fullscreen | forecolor backcolor emoticons code",

            // === Protection and Behavior ===
            'entity_encoding' => 'raw',         // Prevents {{ }} from being HTML-encoded
            'cleanup' => false,                 // Disables automatic cleanup
            'verify_html' => false,             // Prevents TinyMCE from reformatting unknown HTML
            'valid_elements' => '*[*]',         // Allows all elements and attributes
            'protect' => [                      // Protects Mustache/Handlebars blocks from parsing
                '/\{\{[\s\S]*?\}\}/g'
            ],
            'forced_root_block' => '',          // Prevents automatic <p> wrapping

            // === Custom Toolbar Buttons ===
            'setup' => new \yii\web\JsExpression('function(editor) {
                // Small Lorem Ipsum button
                editor.ui.registry.addButton("loremIpsumSmall", {
                    text: "Lorem Small",
                    icon: "edit-block",
                    tooltip: "Insert short Lorem Ipsum text",
                    onAction: function () {
                        let loremText = "Lorem ipsum dolor sit amet, consectetur adipiscing elit.";
                        editor.insertContent(loremText);
                    }
                });

                // Large Lorem Ipsum button
                editor.ui.registry.addButton("loremIpsumBig", {
                    text: "Lorem Big",
                    icon: "edit-block",
                    tooltip: "Insert long Lorem Ipsum text",
                    onAction: function () {
                        let loremText = `' . $textBig . '`;
                        editor.insertContent(loremText);
                    }
                });
            }')
        ], $this->clientOptions);
    }

    /**
     * Renders the widget.
     *
     * Outputs the textarea and initializes TinyMCE using JavaScript.
     * Automatically removes any existing instance on the same selector
     * before initializing again.
     *
     * @return void
     */
    public function run()
    {
        $view = $this->getView();
        $id = $this->options['id'];
        $this->clientOptions['selector'] = "#$id";

        // Set editor language if specified
        if ($this->language !== null && $this->language !== 'en-US') {
            $this->clientOptions['language'] = strtolower(str_replace('-', '_', $this->language));
        }

        $options = Json::encode($this->clientOptions);

        // Render the textarea
        if ($this->hasModel()) {
            echo Html::activeTextarea($this->model, $this->attribute, $this->options);
        } else {
            echo Html::textarea($this->name, $this->value, $this->options);
        }

        // Initialize TinyMCE
        $js = "tinymce.remove('#$id'); tinymce.init($options);";
        $view->registerJs($js);
    }
}
