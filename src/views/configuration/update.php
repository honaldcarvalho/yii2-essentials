<?php

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\Configuration */

use croacworks\essentials\models\Configuration;
use yii\bootstrap5\Html;

$this->title = Yii::t('app', 'Update Param: {name}', [
    'name' => $model->title,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Configuration'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>

<div class="configuration-update">
    <h1><?= Html::encode($this->title) ?></h1>
    <p>
        <?= croacworks\essentials\widgets\DefaultButtons::widget(['show' => ['list']]) ?>
    </p>
    <div class="col-md-12">
        <?php
        if ($model->scenario == Configuration::SCENARIO_ADMIN) {
            echo $this->render('_form_admin', [
                'model' => $model
            ]);
        } else {
            echo $this->render('_form', [
                'model' => $model
            ]);
        }
        ?>
    </div>
</div>
