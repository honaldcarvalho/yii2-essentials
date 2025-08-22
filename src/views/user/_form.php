<?php
/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\User */
/* @var $profile croacworks\essentials\models\UserProfile */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>
<div class="user-form">
<?php $form = ActiveForm::begin([
    'id' => 'user-profile-form',
    'enableAjaxValidation' => true,
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
                0 => Yii::t('app', 'Deleted'),
                9 => Yii::t('app', 'Inactive'),
                10 => Yii::t('app', 'Active'),
                1 => Yii::t('app', 'Active'),
            ], ['prompt' => Yii::t('app', 'Select...')]) ?>

            <?php if ($model->hasAttribute('theme')): ?>
                <?= $form->field($model, 'theme')->dropDownList(['light' => 'Light', 'dark' => 'Dark'], ['prompt' => Yii::t('app', 'Select...')]) ?>
            <?php endif; ?>

            <?php if ($model->hasAttribute('language_id')): ?>
                <?= $form->field($model, 'language_id')->textInput() ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong><?= Yii::t('app', 'Profile') ?></strong></div>
        <div class="card-body">
            <?= $form->field($profile, 'fullname')->textInput(['maxlength' => true]) ?>
            <?= $form->field($profile, 'phone')->textInput(['maxlength' => true]) ?>
            <?= $form->field($profile, 'cpf_cnpj')->textInput(['maxlength' => true]) ?>

            <?php if ($profile->hasAttribute('language_id')): ?>
                <?= $form->field($profile, 'language_id')->textInput() ?>
            <?php endif; ?>

            <?php if ($profile->hasAttribute('theme')): ?>
                <?= $form->field($profile, 'theme')->dropDownList(['light' => 'Light', 'dark' => 'Dark'], ['prompt' => Yii::t('app', 'Select...')]) ?>
            <?php endif; ?>

            <?php if ($profile->hasAttribute('file_id')): ?>
                <?= $form->field($profile, 'file_id')->textInput() ?>
            <?php endif; ?>

            <?= $form->field($profile, 'status')->dropDownList([
                0 => Yii::t('app', 'Inactive'),
                10 => Yii::t('app', 'Active'),
            ], ['prompt' => Yii::t('app', 'Select...')]) ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', $model->isNewRecord ? 'Create' : 'Save'), ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Cancel'), $model->isNewRecord ? ['index'] : ['view', 'id' => $model->id], ['class' => 'btn btn-secondary']) ?>
    </div>

<?php ActiveForm::end(); ?>
</div>
