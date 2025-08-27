<?php

use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\models\Group;;
use croacworks\essentials\models\Language;
use croacworks\essentials\models\User;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\UserUpdate $model */
/** @var yii\widgets\ActiveForm $form */

$query = Group::find();
if(isset($group)) {
    $query->where(['id'=>$group->id]);
}
$groups = $query->andWhere(['<>','name','*'])
->asArray()->all();

$script = <<< JS
    $('#user-group_id').select2({tags:true,placeholder:'-- Selecione um grupo -- ', width:'100%',
        createTag: function (params) {
            var term = $.trim(params.term);
            if (term === '') {
                return null;
            }
            return {
                id: term,
                text: term,
                newTag: true // add additional parameters
            }
        }
    });
JS;

$this->registerJs($script);

?>

<div class="user-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
    <?= $form->field($model, 'file_id')->fileInput([
         'id' => Html::getInputId($model, 'file_id'),
         'accept' => 'image/*', 'style' => 'display:none'
       ])->label(false); ?>

    <?= \croacworks\essentials\widgets\UploadImageInstant::widget([
         'mode'        => 'defer',
         'hideSaveButton' => true,             // só “Cortar” e fechar
         'model'       => $model,
         'attribute'   => 'file_id',
         'fileInputId' => Html::getInputId($model, 'file_id'),
         'imageUrl'    => $model->file->url ?? '',
         'aspectRatio' => '16/9',
    ]) ?>

        </div>
    </div>

    <div class="row">

        <div class="col-md-12">
            
            <?=  AuthorizationController::isAdmin() ? $form->field($model, 'group_id')->dropDownList(yii\helpers\ArrayHelper::map(
            $groups, 'id', 'name'), ['prompt' => '-- Selecione um Grupo --']) : ''?>
            
            <?= $form->field($model, 'fullname')->textInput() ?>
            
            <?= $form->field($model, 'theme')->dropDownList(['light'=>'Light','dark'=>'Dark']) ?>

            <?= $form->field($model, 'phone')->widget(\yii\widgets\MaskedInput::class, [
                'mask' => '(99) 9 9999-9999',
            ]) ?>
            <?= $form->field($model, 'email')->textInput() ?>
            <?= $form->field($model, 'language_id')->dropDownList(yii\helpers\ArrayHelper::map(
                Language::find()
                    ->select('id,name')->asArray()->all(),
                'id',
                'name'
            )) ?>
            <?= $form->field($model, 'password')->passwordInput() ?>
            <?= $form->field($model, 'password_confirm')->passwordInput() ?>

            <?=  AuthorizationController::isAdmin() ? $form->field($model, 'status')->dropDownList([9 => Yii::t('app', 'Inactive'), 10 => Yii::t('app', 'Active'), 20 => Yii::t('app', 'No system user')]) : '' ?>

            <div class="form-group">
                <?= Html::submitButton('<i class="fas fa-save mr-2"></i>' . Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
            </div>

            <?php ActiveForm::end(); ?>

        </div>
    </div>
</div>