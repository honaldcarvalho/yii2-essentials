<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\Role */

$this->title = Yii::t('app', 'Role').': ' .$model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Roles'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
$model->actions = str_replace(';', ' | ', $model->actions);
$model->origin = str_replace(';', ' | ', $model->origin);


?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <p>
                        <?= croacworks\essentials\widgets\DefaultButtons::widget(['controller' => 'Role','model'=>$model,'verGroup'=>false]) ?>
                        <?= Html::a(Yii::t('app', 'Add Roles'), ['/apply-templates','reseed'=> 1, 'group_id' => $model->group_id], ['class' => 'btn btn-default']) ?>
                        <?= Html::a(Yii::t('app', 'Add Roles'), ['remove-roles', 'group_id' => $model->group_id], ['class' => 'btn btn-default']) ?>
                    </p>
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            'user.fullname:text:'.Yii::t('app', 'User'),
                            'group.name:text:'.Yii::t('app', 'Role'),
                            'controller',
                            'actions',
                            'origin',
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
