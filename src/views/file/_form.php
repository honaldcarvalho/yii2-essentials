<?php

use yii\helpers\Html;
use yii\web\View;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\BootstrapAsset;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\File $model */
/** @var yii\widgets\ActiveForm $form */

?>

<div class="file-form">

  <?php $form = ActiveForm::begin(); ?>
    
    <?= $form->field($model, 'folder_id')->dropDownList(yii\helpers\ArrayHelper::map(croacworks\essentials\models\Folder::find()->asArray()->all(), 
            'id', 'name'), ['prompt' => Yii::t('app','-- Select Folder --')]) ?>
    
    <?= $form->field($model, 'caption')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>
    


  <div class="form-group">
    <?= Html::submitButton('Salvar', ['class' => 'btn btn-success']) ?>
  </div>

  <?php ActiveForm::end(); ?>

</div>