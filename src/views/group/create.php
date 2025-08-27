<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\Group */

$this->title = Yii::t('app', 'Create Group');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Groups'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>


<div class="user-create">
    <?= $this->render('_form', [
        'model' => $model
    ]) ?>
</div>