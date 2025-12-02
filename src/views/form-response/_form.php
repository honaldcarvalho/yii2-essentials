<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use croacworks\essentials\widgets\DynamicFormWidget;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\FormResponse $model */

// The widget you sent exposes: public $model; public $ajax=true; public $action=null; etc.
// It renders inputs for response_data according to the FormResponse->dynamic_form_id.

$form = ActiveForm::begin([
    'options' => ['enctype' => 'multipart/form-data'],
]);

echo DynamicFormWidget::widget([
    'formId' =>  $model->dynamic_form_id,
    'model' => $model
]);

ActiveForm::end();
