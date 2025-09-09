<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \frontend\models\ResetPasswordForm $model */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Reset password';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="bg-body-tertiary min-vh-100 d-flex flex-row align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card-group d-block d-md-flex row">
                    <div class="card col-md-7 p-4 mb-0">
                        <div class="card-body">
                            <h3><?= Html::encode($this->title) ?></h3>

                            <p><?= Yii::t('app', 'Please choose your new password:'); ?></p>

                            <div class="col-lg-12">
                                <?php $form = ActiveForm::begin(['id' => 'reset-password-form']); ?>

                                <?= $form->field($model, 'password')->passwordInput(['autofocus' => true]) ?>

                                <div class="form-group mb-3 mt-3">
                                    <?= Html::submitButton('Save', ['class' => 'btn btn-primary']) ?>
                                </div>

                                <?php ActiveForm::end(); ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>