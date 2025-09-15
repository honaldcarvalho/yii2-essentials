<?php

use yii\helpers\Html;
use yii\grid\GridView;
use croacworks\essentials\components\gridview\ActionColumnCustom;

/* @var $this yii\web\View */
/* @var $searchModel croacworks\essentials\models\ParamSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Configuration');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-12">
                            <?= Html::a(Yii::t('app', 'New Configuration'), ['create'], ['class' => 'btn btn-success']) ?>
                        </div>
                    </div>


                    <?php echo $this->render('/_parts/filter', ['view' =>'/configuration','searchModel' => $searchModel]); ?>

                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'columns' => [
                            'id',
                            'description',
                            //'file_id',
                            //'meta_viewport',
                            //'meta_author',
                            //'meta_robots',
                            //'meta_googlebot',
                            //'meta_keywords',
                            //'meta_description',
                            //'canonical',
                            'host',
                            'title',
                            //'bussiness_name',
                            'email:email',
                            [
                                'attribute'=>'created_at',
                                'format' => 'date',
                                'label' => Yii::t('app', 'Created At'),
                                'filter' =>Html::input('date', ucfirst(Yii::$app->controller->id).'Search[created_at]',$searchModel->created_at,['class'=>'form-control dateandtime'])
                            ],
                            [
                                'attribute'=>'updated_at',
                                'format' => 'date',
                                'label' => Yii::t('app', 'Updated At'),
                                'filter' =>Html::input('date',ucfirst(Yii::$app->controller->id).'Search[updated_at]',$searchModel->updated_at,['class'=>'form-control dateandtime'])
                            ],
                            'status:boolean',
                            [
                                'class' => 'croacworks\essentials\components\gridview\ActionColumnCustom',
                                'template' => '{clone} {view} {update} {delete}',
                            ]
                        ],
                        'summaryOptions' => ['class' => 'summary mb-2'],
                        'pager' => [
                            'class' => 'yii\bootstrap5\LinkPager',
                        ]
                    ]); ?>


                </div>
                <!--.card-body-->
            </div>
            <!--.card-->
        </div>
        <!--.col-md-12-->
    </div>
    <!--.row-->
</div>
