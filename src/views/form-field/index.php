<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $searchModel app\models\AgendaSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Form Fields');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <?= \croacworks\essentials\widgets\DefaultButtons::widget([
                                'controller' => 'FormField',
                                'show' => ['create'],
                                'buttons_name' => ['create' => Yii::t('app', 'Create Form Field')],
                                'verGroup' => true,
                            ]) ?> </div>
                    </div>


                    <?php Pjax::begin(); ?>
                    <?php echo $this->render('/_parts/filter', ['view' =>"/form-field",'searchModel' => $searchModel]); 
                    ?>
                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'columns' => [
                            ['class' => 'yii\grid\SerialColumn'],

                            'id',
                            'dynamic_form_id',
                            'label',
                            'name',
                            'type',
                            //'default',
                            //'model_class',
                            //'model_field',
                            //'order',
                            //'status:boolean',

                            ['class' => 'croacworks\essentials\components\gridview\ActionColumn'],
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