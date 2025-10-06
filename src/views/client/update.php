<?php

/* @var $this yii\web\View */
/* @var $model app\models\custom\Client */

$this->title = Yii::t('app', 'Update Client: {name}', [
    'fullname' => $model->fullname,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Clients'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->fullname, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            
            <h1><?= yii\bootstrap5\Html::encode($this->title) ?></h1>

            <div class="card">
                <div class="card-body">

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <?= croacworks\essentials\widgets\DefaultButtons::widget([
                                'show' => ['index'],
                                'buttons_name' => ['index' => Yii::t('app', 'List') . ' ' . Yii::t('app', 'Clients')]
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
