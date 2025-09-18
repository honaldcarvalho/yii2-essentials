<?php

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\Role */

$this->title = Yii::t('app', 'Update Role: {name}', [
    'name' => $model->id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Roles'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>

<div class="container-fluid">
    <h1><?= yii\bootstrap5\Html::encode($this->title) ?></h1>

    <div class="card">
        <div class="card-body">

            <div class="row mb-2">
                <div class="col-md-12">
                    <?= croacworks\essentials\widgets\DefaultButtons::widget([
                        'show' => ['index'],
                        'buttons_name' => ['index' => Yii::t('app', 'List') . ' ' . Yii::t('app', 'Roles'),'verGroup'=>false]
                    ]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?=$this->render('_form', [
                        'model' => $model
                    ]) ?>
                </div>
            </div>
        </div>
        <!--.card-body-->
    </div>
    <!--.card-->
</div>