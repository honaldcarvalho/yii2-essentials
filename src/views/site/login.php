<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var croacworks\essentials\models\forms\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Login';
?>

<div class="bg-body-tertiary min-vh-100 d-flex flex-row align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card-group d-block d-md-flex row">
                    <div class="card col-md-7 p-4 mb-0">
                        <div class="card-body">
                            <h1>Login</h1>
                            <p class="text-body-secondary">Sign In to your account</p>
                            <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>
                            <div class="input-group mb-3"><span class="input-group-text">
                                    <svg class="icon">
                                        <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-user"></use>
                                    </svg></span>
                                <input class="form-control" name="LoginForm[username]" type="text" placeholder="Username">
                            </div>
                            <div class="input-group mb-4"><span class="input-group-text">
                                    <svg class="icon">
                                        <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-lock-locked"></use>
                                    </svg></span>
                                <input class="form-control" name="LoginForm[password]" type="password" placeholder="Password">
                            </div>
                            <div class="input-group mb-4"><span class="input-group-text">
                                    <?= $form->field($model, 'rememberMe')->checkbox() ?>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <button class="btn btn-primary px-4" type="button">Login</button>
                                </div>
                                <div class="col-6 text-end">
                                    <?= Yii::t('app', ' If you forgot your password you can ') . Html::a('reset it', ['site/request-password-reset']) ?>.
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