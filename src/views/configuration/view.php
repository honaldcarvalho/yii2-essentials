<?php

use croacworks\essentials\widgets\AppendModel;
use croacworks\essentials\widgets\Attact;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\Configuration */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Configuration'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <p>
                        <?= croacworks\essentials\widgets\DefaultButtons::widget(['controller' => 'Configuration','model'=>$model,'verGroup'=>false]) ?>
                    </p>
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            'description',
                            [
                                'attribute'=>'file_id',
                                'format'=>'raw',
                                'value'=> function($data){
                                    if(!empty($data->file_id) && $data->file !== null){
                                        $url = Yii::getAlias('@web').$data->file->urlThumb;
                                        return "<img class='brand-image img-circle elevation-3' width='50' src='{$url}' />";
                                    }
                                }
                            ],
                            'host',
                            'title',
                            'bussiness_name',
                            'email:email',
                            'created_at:datetime',
                            'updated_at:datetime',
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

    <?= AppendModel::widget([
        'attactModel'=>'Parameter',
        'uniqueId'=>'ParameterAppend',
        'controller'=>'configuration',
        'template' => '{edit}{remove}',
        'attactClass'=>'weebz\\yii2basics\\models\\Parameter',
        'dataProvider' => new \yii\data\ActiveDataProvider([
            'query' => $model->getParameters(),
        ]),
        'showFields'=>['description','name',
        [
            'attribute' => 'value',
            'contentOptions' => ['class' => 'text-trucate'], 
        ],
        'status:boolean'],
        'fields'=>
        [
            [
                'name'=>'configuration_id',
                'type'=>'hidden',
                'value'=>$model->id
            ],
            [
                'name'=>'description',
                'type'=>'text'
            ],
            [
                'name'=>'name',
                'type'=>'text'
            ],
            [
                'name'=>'value',
                'type'=>'text'
            ],
            [
                'name'=>'status',
                'type'=>'checkbox'
            ],
        ]
    ]); ?>

    <?= AppendModel::widget([
        'attactModel'=>'MetaTag',
        'uniqueId'=>'MetaTagAppend',
        'controller'=>'configuration',
        'template' => '{edit}{remove}',
        'attactClass'=>'weebz\\yii2basics\\models\\MetaTag',
        'dataProvider' => new \yii\data\ActiveDataProvider([
            'query' => $model->getMetaTags(),
        ]),
        'showFields'=>['description','name','content'],
        'fields'=>
        [
            [
                'name'=>'configuration_id',
                'type'=>'hidden',
                'value'=>$model->id
            ],
            [
                'name'=>'description',
                'type'=>'text'
            ],
            [
                'name'=>'name',
                'type'=>'text'
            ],
            [
                'name'=>'content',
                'type'=>'text'
            ],
        ]
    ]); ?>
</div>