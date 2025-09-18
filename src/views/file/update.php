<?php

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\File */

use yii\bootstrap5\Html;

$this->title = Yii::t('app', 'Update File: {name}', [
    'name' => $model->name,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Files'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            
            <h1><?= Html::encode($this->title) ?></h1>

            <div class="card">
                <div class="card-body">

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <?= croacworks\essentials\widgets\DefaultButtons::widget([
                                'show' => ['index'],
                                'buttons_name' => ['index' => Yii::t('app', 'List') . ' ' . Yii::t('app', 'Pages')]
                            ]) ?>
                        </div>
                    </div>

                    <?=$this->render('_form', [
                        'model' => $model
                    ]) ?>

                </div>
                <!--.card-body-->
            </div>
            <!--.card-->
        </div>
        <!--.col-md-12-->
    </div>
    <!--.row-->
</div>
