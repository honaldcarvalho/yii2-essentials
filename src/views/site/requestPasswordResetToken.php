<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \frontend\models\PasswordResetRequestForm $model */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Request password reset';
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

                            <p><?=Yii::t('app','Please fill out your email. A link to reset password will be sent there.');?></p>


                            <div class="col-lg-12">
                                <?php $form = ActiveForm::begin(['id' => 'request-password-reset-form']); ?>

                                <?= $form->field($model, 'email')->textInput(['autofocus' => true]) ?>

                                <div class="form-group">
                                    <?= Html::submitButton('Send', ['class' => 'btn btn-primary']) ?>
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