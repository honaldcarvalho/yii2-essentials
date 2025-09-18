<?php

use croacworks\essentials\widgets\DefaultButtons;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel croacworks\essentials\models\LicenseTypeSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'License Types');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="container-fluid">
    <div class="row">
        <h1><?= yii\bootstrap5\Html::encode($this->title) ?></h1>
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                    <div class="col-md-12">
                            <?= croacworks\essentials\widgets\DefaultButtons::widget(
                            [
                                'controller' => Yii::$app->controller->id,'show' => ['create']
                            ]) ?>
                        </div>
                    </div>


                    <?php echo $this->render('/_parts/filter', ['view' =>'/license-type','searchModel' => $searchModel]); ?>

                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'columns' => [
                            'id',
                            'name',
                            'value',
                            'contract:ntext',
                            'max_devices',
                            'status:boolean',

                            ['class' =>'croacworks\essentials\components\gridview\ActionColumnCustom',],
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
