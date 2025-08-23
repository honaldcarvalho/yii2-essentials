<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel croacworks\essentials\models\FolderSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Files');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="container-fluid">
    

    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-12">
                            <?= croacworks\essentials\widgets\DefaultButtons::widget(['controller' => 'Folder','show'=>['create'],'buttons_name'=>['create'=>'Create Folder']]) ?>
                        </div>
                    </div>
                    
                    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        'columns' => [
       
                            'id',
                            'name',
                            'description',
                            'external:boolean',
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

                            ['class' =>'croacworks\essentials\components\ActionColumn',],
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
