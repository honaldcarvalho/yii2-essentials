<?php

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\Page */

use yii\bootstrap5\Html;

$this->title = Yii::t('app', 'Update Page: {name}', [
    'name' => $model->title,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Pages'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>

<div class="page-index">
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
