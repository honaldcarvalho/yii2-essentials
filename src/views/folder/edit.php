<?php

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\Folder */

use croacworks\essentials\controllers\AuthorizationController;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = Yii::t('app', 'Update Folder: {name}', [
    'name' => $model->name,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Folders'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');

$script = <<< JS
    $(document).on('focusin', function(e) {
    if ($(e.target).closest(".tox-tinymce, .tox-tinymce-aux, .moxman-window, .tam-assetmanager-root").length) {
        e.stopImmediatePropagation();
    }
    });

  window.addEventListener('message', function(event) {
    if(event.data == 'show'){
        $('#overlay').show();
    }else{
        $('#overlay').hide();
    }
  });

  $('#btn-save-file').click(function(){
    let string_json = '{"action":"update","id":$model->id,"form":"'+$('#form-file').serialize()+'"}';
    window.parent.postMessage(JSON.parse(string_json), '*');
  });

  $(function(){
  });

JS;

$this::registerJs($script);
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="folder-form">

                        <?php $form = ActiveForm::begin(['id' => 'form-file']); ?>

                        <?= $form->field($model, 'folder_id')->dropDownList(yii\helpers\ArrayHelper::map(croacworks\essentials\models\Folder::find()
                            ->where(['in', 'group_id',  AuthorizationController::userGroups()])
                            ->asArray()->all(), 'id', 'name'), ['class' => 'form-control', 'prompt' => Yii::t('app', '-- Select Folder --')]) ?>

                        <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

                        <?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>

                        <?= $form->field($model, 'external')->checkbox() ?>

                        <?= $form->field($model, 'status')->checkbox() ?>

                        <div class="form-group mb-3 mt-3">
                            <a id="btn-save-slide" class="btn btn-success"><i class="fas fa-save"></i> <?= Yii::t('app', 'Save'); ?></a>
                            <a class="btn btn-secondary" data-dismiss="modal" id="btn-save-slide" href="javascript:window.parent.postMessage('close', '*');">
                                <i class="fas fa-close"></i>
                                <?= Yii::t('app', 'Close'); ?></a>
                        </div>

                        <?php ActiveForm::end(); ?>

                    </div>

                </div>
            </div>
        </div>
        <!--.card-body-->
    </div>
    <!--.card-->
</div>