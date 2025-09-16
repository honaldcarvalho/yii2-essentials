<?php

use croacworks\essentials\widgets\DefaultButtons;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel croacworks\essentials\models\SourceMessageSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Source Messages');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                    <div class="col-md-12">
                            <?= croacworks\essentials\widgets\DefaultButtons::widget(
                            [
                                'controller' => Yii::$app->controller->id,'verGroup'=>false,
                                'show'=>['create'],'buttons_name'=>['create'=>Yii::t('app',"Create Source Message")]
                            ]) ?>
                        </div>
                    </div>


                    <?php echo $this->render('/_parts/filter', ['view' =>'/source-message','searchModel' => $searchModel]); ?>

                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'columns' => [
                             'id',
                            'category',
                            'message:ntext',
                            ['class' =>'croacworks\essentials\components\gridview\ActionColumnCustom','verGroup'=>false]
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
