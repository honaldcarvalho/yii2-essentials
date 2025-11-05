<?= \app\components\widgets\DynamicFormWidget::widget([
    'formId' => $model->dynamic_form_id,
    'model' => $model,
    'ajax' => true,
]) ?>