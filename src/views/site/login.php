<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var croacworks\essentials\models\forms\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = Yii::t('app','Signin');

?>
<div class="bg-body-tertiary min-vh-100 d-flex flex-row align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card-group d-block d-md-flex row">
                    <div class="card col-md-7 p-4 mb-0">
                        <div class="card-body">
                            <h1><?= Yii::t('app', 'Signin') ?></h1>
                            <p class="text-body-secondary">
                                <?= Yii::t('app', 'Sign in to your account') ?>
                            </p>

                            <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>
                            <?= $form->errorSummary($model, ['class' => 'alert alert-danger']); ?>

                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="fas fa-user icon"></i></span>
                                <input class="form-control"
                                    name="LoginForm[username]"
                                    type="text"
                                    placeholder="<?= Html::encode(Yii::t('app', 'Username')) ?>">
                            </div>

                            <div class="input-group mb-4">
                                <span class="input-group-text"><i class="fas fa-key icon"></i></span>
                                <input class="form-control"
                                    name="LoginForm[password]"
                                    type="password"
                                    placeholder="<?= Html::encode(Yii::t('app', 'Password')) ?>">
                            </div>

                            <div class="mb-4">
                                <?= $form->field($model, 'rememberMe')
                                    ->checkbox()
                                    ->label(Yii::t('app', 'Remember me')) ?>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <?= Html::submitButton(
                                        Yii::t('app', 'Signin'),
                                        ['class' => 'btn btn-primary px-4', 'name' => 'login-button']
                                    ) ?>
                                </div>
                                <div class="col-6 text-end">
                                    <?= Yii::t('app', 'If you forgot your password you can') . ' ' .
                                        Html::a(Yii::t('app', 'reset it'), ['site/request-password-reset']) ?>.
                                </div>
                            </div>

                            <?php ActiveForm::end(); ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>