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
                                'buttons_name' => ['index' => Yii::t('app', 'List') . ' ' . Yii::t('app', 'Configurations')]
                            ]) ?>
                        </div>
                    </div>

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
                <!--.card-body-->
            </div>
            <!--.card-->
        </div>
        <!--.col-md-12-->
    </div>
    <!--.row-->
</div>