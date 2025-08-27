<?php
/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\User */
/* @var $profile croacworks\essentials\models\UserProfile */

use croacworks\essentials\models\Language;
use croacworks\essentials\models\User;
use croacworks\essentials\widgets\UploadImageInstant;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>
<div class="user-form">
<?php $form = ActiveForm::begin([
    'id' => 'user-profile-form',
    'enableAjaxValidation' => false,
]); ?>

    <div class="card mb-4">
        <div class="card-header"><strong><?= Yii::t('app', 'User') ?></strong></div>
        <div class="card-body">
            <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>

            <?php
            // Campos de senha (virtuais)
            echo $form->field($model, 'password')->passwordInput(['maxlength' => true])
                ->hint($model->isNewRecord
                    ? Yii::t('app', 'At least 6 characters.')
                    : Yii::t('app', 'Leave blank to keep the current password.')
                );

            echo $form->field($model, 'password_confirm')->passwordInput(['maxlength' => true])
                ->hint($model->isNewRecord
                    ? Yii::t('app', 'Repeat the password for confirmation.')
                    : Yii::t('app', 'Fill only if you are changing the password.')
                );
            ?>
    
            <?= $form->field($model, 'status')->dropDownList([
                User::STATUS_ACTIVE => Yii::t('app', 'Active'),
                User::STATUS_DELETED => Yii::t('app', 'Deleted'),
                User::STATUS_INACTIVE => Yii::t('app', 'Inactive'),
            ]) ?>

            <?php if ($model->hasAttribute('language_id')): ?>
                <?= $form->field($model, 'language_id')->textInput() ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong><?= Yii::t('app', 'Profile') ?></strong></div>
        <div class="card-body">
            <div class="col-sm-12">
                <?= $form->field($profile, 'file_id')
                    ->fileInput([
                        'id' => \yii\helpers\Html::getInputId($profile, 'file_id'),
                        'accept' => 'image/*',
                        'style' => 'display:none'
                    ])->label(false) ?>

                <?= UploadImageInstant::widget([
                    'mode'        => 'defer',
                    'model'       => $profile,
                    'attribute'   => 'file_id',
                    'fileInputId' => \yii\helpers\Html::getInputId($profile, 'file_id'),
                    'imageUrl'    => $profile->file->url ?? '',
                    'aspectRatio' => '1',
                ]) ?>
            </div>
            <?= $form->field($profile, 'fullname')->textInput(['maxlength' => true]) ?>
            <?= $form->field($profile, 'cpf_cnpj')->textInput(['maxlength' => true]) ?>

            <?= $form->field($profile, 'phone')->widget(\yii\widgets\MaskedInput::class, [
                'mask' => '(99) 9 9999-9999',
            ]) ?>
            <?= $form->field($profile, 'language_id')->dropDownList(yii\helpers\ArrayHelper::map(
                Language::find()
                    ->select('id,name')->asArray()->all(),
                'id',
                'name'
            )) ?>

            <?php if ($profile->hasAttribute('theme')): ?>
                <?= $form->field($profile, 'theme')->dropDownList(['light' => 'Light', 'dark' => 'Dark']) ?>
            <?php endif; ?>

        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', $model->isNewRecord ? 'Create' : 'Save'), ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Cancel'), $model->isNewRecord ? ['index'] : ['view', 'id' => $model->id], ['class' => 'btn btn-secondary']) ?>
    </div>

<?php ActiveForm::end(); ?>
</div>
