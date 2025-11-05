<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $searchModel app\models\AgendaSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Dynamic Forms');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <?= \weebz\yii2basics\widgets\DefaultButtons::widget([
                                'controller' => 'DynamicForm',
                                'show' => ['create'],
                                'buttons_name' => ['create' => Yii::t('app', 'Create Dynamic Form')],
                                'verGroup' => true,
                            ]) ?> </div>
                    </div>


                    <?php Pjax::begin(); ?>
                    <?php echo $this->render('/_parts/filter', ['view' => "/dynamic-form", 'searchModel' => $searchModel]); ?>
                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'columns' => [
                            ['class' => 'yii\grid\SerialColumn'],

                            'id',
                            'name',
                            'description',
                            'status:boolean',

                            ['class' => 'weebz\yii2basics\components\gridview\ActionColumn'],
                        ],
                        'summaryOptions' => ['class' => 'summary mb-2'],
                        'pager' => [
                            'class' => 'yii\bootstrap5\LinkPager',
                        ]
                    ]); ?>

                    <?php Pjax::end(); ?>

                </div>
                <!--.card-body-->
            </div>
            <!--.card-->
        </div>
        <!--.col-md-12-->
    </div>
    <!--.row-->
</div>