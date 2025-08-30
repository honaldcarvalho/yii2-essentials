<?php

use croacworks\essentials\widgets\DefaultButtons;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\LicenseType */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'License Types'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-12">
                    <?= DefaultButtons::widget(
                    [
                        'controller' => Yii::$app->controller->id,'model'=>$model,'verGroup'=>false
                    ]) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            'name',
                            'value',
                            'max_devices',
                            'description:html',
                            'contract:html',
                            'status:boolean',
                        ],
                    ]) ?>
                </div>
                <!--.col-md-12-->
            </div>
            <!--.row-->
        </div>
        <!--.card-body-->
    </div>
    <!--.card-->
</div>