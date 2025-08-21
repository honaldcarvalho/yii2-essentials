<?php

use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\Group */

$this->title = Yii::t('app', 'View Group: {name}', [
    'name' => $model->name,
]);

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Groups'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

$buttons[] = 
[
    'controller'=>'user',
    'action'=>'create',
    'icon'=>'<i class="fas fa-plus-square mr-2"></i>',
    'text'=>Yii::t('app','Add User'),
    'link'=>"/user/create?id={$model->id}",
    'options'=>                    [
        'class' => 'btn btn-success btn-block-m',
    ],
];

?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <p>
                        <?php //croacworks\essentials\widgets\DefaultButtons::widget(['controller' => 'Group','model'=>$model,'extras'=>$buttons,'verGroup'=>false]) ?>
                    </p>
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            'name',
                            'status:boolean',
                        ],
                    ]) ?>
                </div>
                <!--.col-md-12-->
                    <div class="container-fluid">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?=Yii::t('app','Users')?></h3>
                            </div>
                            
                            <div class="card-body">
                                <div class="row">

                                    <div class="col-md-12">
                                        
                                        <?= GridView::widget([
                                            'dataProvider' => $users,
                                            'columns' => [
                                                'user.fullname',
                                                'user.email',
                                                [
                                                    'attribute'=>'user.created_at',
                                                    'format' => 'date',
                                                    'label' => Yii::t('app', 'Created At'),
                                                ],
                                                [
                                                    'attribute'=>'user.updated_at',
                                                    'format' => 'date',
                                                    'label' => Yii::t('app', 'Updated At'),
                                                ],
                                                'user.status:boolean',
                                                // [
                                                //     'class' =>'croacworks\essentials\components\gridview\ActionColumn',
                                                //     'controller'=>'user',
                                                //     'verGroup'=>false,
                                                //     'template' => '{update} {delete}'  ,
                                                //     'buttons' => [  
                                                //         'update' => function ($url, $model, $key) {
                                                //             return Html::a('<i class="fas fa-pen"></i>', yii\helpers\Url::to(['/user/update', 'id' => $model->user_id]),['class'=>'btn btn-default']);
                                                //         },   
                                                //         'delete' => function ($url, $model, $key) {
                                                //             return Html::a('<i class="fas fa-unlink"></i>', yii\helpers\Url::to(['/user/remove-group', 'id' => $model->id]),['class'=>'btn btn-default']);
                                                //         }    
                                                //     ]
                                                // ],
                                            ],
                                        ]); ?>

                                    </div>
                                </div>
                            </div>
                            <!--.card-body-->
                        </div>
                        <!--.card-->
                    </div>
            </div>
            <!--.row-->
        </div>
        <!--.card-body-->
    </div>
    <!--.card-->
</div>