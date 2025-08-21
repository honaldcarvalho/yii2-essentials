<?php

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

use croacworks\essentials\controllers\AuthController;
use yii\helpers\Html;

$this->title = \Yii::t('app',$name);
$this->params['breadcrumbs'] = [['label' => $this->title]];

if( AuthController::isGuest())
    $this->context->layout = '@vendor/weebz/yii2-basics/src/themes/adminlte3/views/layouts/main-blank'; // Use a specific layout for error pages

?>

<div class="error-page">
    <div class="error-content" style="margin-left: auto;">
        <h3><i class="fas fa-exclamation-triangle text-danger"></i> <?= \Yii::t('app',$name); ?></h3>

        <p>
            <?= \Yii::t('app','The above error occurred while the Web server was processing your request.'); ?></b>
        </p>
    </div>
</div>

