<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\LicenseType */
/* @var $form yii\bootstrap5\ActiveForm */

$script = <<< JS

    $(function(){

        tinymce.init({
            selector: '#licensetype-description',
            height: 500,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | ' +
            'bold italic backcolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | fontsize',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }',
            font_size_formats: '8pt 10pt 12pt 14pt 16pt 18pt 24pt 36pt 48pt 50pt 55pt 60pt'
        });

        tinymce.init({
            selector: '#licensetype-contract',
            height: 500,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | ' +
            'bold italic backcolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | fontsize',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }',
            font_size_formats: '8pt 10pt 12pt 14pt 16pt 18pt 24pt 36pt 48pt 50pt 55pt 60pt'
        });

    });


JS;

$this::registerJsFile(Yii::getAlias('@web/') . 'plugins/tinymce/tinymce.min.js', ['depends' => [\yii\web\JqueryAsset::class]]);
$this::registerJs($script, $this::POS_END);

?>

<div class="license-type-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'value')->input('number',['maxlength' => true, "step"=>"0.01"]) ?>

    <?= $form->field($model, 'contract')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'max_devices')->input('number') ?>

    <?= $form->field($model, 'status')->checkbox() ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
