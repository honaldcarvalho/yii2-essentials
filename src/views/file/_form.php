<?php

use yii\helpers\Html;
use yii\web\View;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\BootstrapAsset;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\File $model */
/** @var yii\widgets\ActiveForm $form */

$this->registerJsFile(
  '@web/plugins/jquery-cropper/cropper.min.js',
  ['depends' => [\yii\web\JqueryAsset::class]]
);

$this->registerJsFile(
  '@web/plugins/jquery-cropper/jquery-cropper.min.js',
  ['depends' => [\yii\web\JqueryAsset::class]]
);

$this->registerCssFile("@web/plugins/jquery-cropper/cropper.min.css", [
  'depends' => [BootstrapAsset::class],
], 'cropper');

$script = <<< JS

var editing = false;
var cropperImage = null;
var image = null;
var imageCroped = null;
var preview = document.getElementById('preview');

function startCrop() {
    $('.crop-tool').removeClass('d-none');
    $('#btn-start-crop').addClass('d-none');
    image = $('#preview');
    image.cropper({
      aspectRatio: $aspectRatio,
      crop: function (event) { }
    });
    cropperImage = image.data('cropper');
}

function cancelCrop() {
    $('.crop-tool').addClass('d-none');
    $('#btn-start-crop').removeClass('d-none');
    image.cropper("destroy");
}

function setCrop() {

    $('.crop-tool').addClass('d-none');
    $('#btn-start-crop').removeClass('d-none');
    var canvas = cropperImage.getCroppedCanvas();
    var data = canvas.toDataURL();
    preview.src = data;
    image.cropper("destroy");

    canvas.toBlob(function (blob) {
        var file = new File([blob], "imagem.jpg", {type: "image/jpeg", lastModified: new Date().getTime()});
        var container = new DataTransfer();
        container.items.add(file);
        var fileField = document.getElementById('file-file');
        fileField.files = container.files;
        console.log(fileField.files);
    });

}

$(document).ready(function(){

    $('#btn-start-crop').click(function () {
      if (editing === false) {
        startCrop();
        editing = true;
      }
    });

    $('#btn-cancel-crop').click(function () {
      if (editing) {
        cancelCrop();
        editing = false;
      }
    });

    $('#btn-set-crop').click(function () {
      if (editing) {
        setCrop();
        editing = false;
      }
    });

}); 

function encodeImageFileAsURL(element) {
    var file = element.files[0];
    var reader = new FileReader();
    if(element.files[0].type == 'image/jpeg' || element.files[0].type == 'image/png' || element.files[0].type == 'image/jpg' ){
        reader.onloadend = function () {
          preview.src = reader.result;
          preview.style.display = 'block';
          $('#preview-group').removeClass('d-none');
        }
        reader.readAsDataURL(file);
    }else{
        preview.style.display = 'none';
        $('#preview-group').addClass('d-none');
    }
}

JS;
$this->registerJs($script, View::POS_END);
?>

<div class="file-form">

  <div class="form-group d-none" id="preview-group">
    <label class="control-label">Preview</label>
    <img src="" id="preview" style="display:none; width: auto;height:350px;">
    <a class="btn btn-info" href="javascript:;" id="btn-start-crop">Editar</a>
    <a class="btn btn-success crop-tool d-none" href="javascript:;" id="btn-set-crop">Cortar</a>
    <a class="btn btn-warning crop-tool d-none" href="javascript:;" id="btn-cancel-crop">Cancelar</span></a>
  </div>

  <?php $form = ActiveForm::begin(); ?>
    
    <?= $form->field($model, 'folder_id')->dropDownList(yii\helpers\ArrayHelper::map(croacworks\essentials\models\Folder::find()->asArray()->all(), 
            'id', 'name'), ['prompt' => Yii::t('app','-- Select Folder --')]) ?>
    
    <?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>
    
    <?= $form->field($model, 'file',['enableClientValidation' => $model->isNewRecord])->fileInput(['onchange'=>"encodeImageFileAsURL(this)","accept"=>$accept])  ?>


  <div class="form-group">
    <?= Html::submitButton('Salvar', ['class' => 'btn btn-success']) ?>
  </div>

  <?php ActiveForm::end(); ?>

</div>