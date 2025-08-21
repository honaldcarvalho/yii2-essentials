<?php

namespace croacworks\essentials\components\gridview;

use Yii;
use yii\bootstrap5\Html;
use yii\grid\DataColumn;

class ResponsiveDataColumn extends DataColumn
{
    public $responsive = true;


    public function init()
    {
        parent::init();

        if (!isset($this->contentOptions['data-title']) && $this->responsive) {
            $label =  Yii::t('app',$this->label);

            // Tenta pegar do modelo, se possível
            if (!$label && $this->grid && $this->attribute) {
                $models = $this->grid->dataProvider->getModels();
                if (!empty($models)) {
                    $model = reset($models);
                    $labels = $model->attributeLabels();
                    $label = $labels[$this->attribute] ?? ucfirst($this->attribute);
                }
            }

            $this->contentOptions['data-title'] = Html::encode($label);
        }

        // Classe útil para CSS
        $this->contentOptions['class'] = ($this->contentOptions['class'] ?? '') . ' col-' . $this->attribute;
    }
}
