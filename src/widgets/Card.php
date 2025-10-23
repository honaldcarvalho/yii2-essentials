<?php

namespace croacworks\essentials\widgets;

use yii\base\Widget;
use yii\helpers\Html;

/**
 * Simple CoreUI Card widget.
 */
class Card extends Widget
{
    public $title;
    public $icon;
    public $options = [];
    public $bodyOptions = [];

    public function init()
    {
        parent::init();

        Html::addCssClass($this->options, 'card shadow-sm mb-4');
        Html::addCssClass($this->bodyOptions, 'card-body');

        echo Html::beginTag('div', $this->options);
        echo Html::beginTag('div', ['class' => 'card-header bg-light fw-bold']);
        if ($this->icon) {
            echo Html::tag('i', '', ['class' => "{$this->icon} me-2"]);
        }
        echo Html::encode($this->title);
        echo Html::endTag('div');
        echo Html::beginTag('div', $this->bodyOptions);
    }

    public function run()
    {
        echo Html::endTag('div'); // .card-body
        echo Html::endTag('div'); // .card
    }
}
