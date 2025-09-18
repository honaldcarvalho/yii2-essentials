<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\Page */

$this->title = Yii::t('app', 'Create Page');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Pages'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="page-create">
    <h1><?= Html::encode($this->title) ?></h1>
    <p>
        <?= croacworks\essentials\widgets\DefaultButtons::widget(['show' => ['list']]) ?>
    </p>
    <div class="col-md-12">
        <?= $this->render('_form', [
            'model' => $model
        ]) ?>
    </div>
</div>
