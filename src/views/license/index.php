<?php

use croacworks\essentials\components\gridview\ActionColumnCustom;
use croacworks\essentials\widgets\DefaultButtons;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel croacworks\essentials\models\LicenseSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Licenses');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1><?= yii\bootstrap5\Html::encode($this->title) ?></h1>
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                    <div class="col-md-12">
                            <?= croacworks\essentials\widgets\DefaultButtons::widget(
                            [
                                'show' => ['create'],'verGroup'=>false
                            ]) ?>
                        </div>
                    </div>

                    <?php echo $this->render('/_parts/filter', ['view' =>'/license','searchModel' => $searchModel]); ?>

                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'columns' => [
                            'id',
                            'licenseType.name:text:'.Yii::t('app','License Type'),
                            'group.name:text:'.Yii::t('app','Group'),
                            [
                                'attribute' => 'validate',
                                'format' => 'date',
                                'label' => Yii::t('app', 'Validate'),
                                'filter' => Html::input('date', ucfirst(Yii::$app->controller->id) . 'Search[validate]', $searchModel->validate, ['class' => 'form-control dateandtime'])
                            ],
                            [
                                'attribute' => 'created_at',
                                'format' => 'date',
                                'label' => Yii::t('app', 'Created At'),
                                'filter' => Html::input('date', ucfirst(Yii::$app->controller->id) . 'Search[created_at]', $searchModel->created_at, ['class' => 'form-control dateandtime'])
                            ],
                            [
                                'attribute' => 'updated_at',
                                'format' => 'date',
                                'label' => Yii::t('app', 'Updated At'),
                                'filter' => Html::input('date', ucfirst(Yii::$app->controller->id) . 'Search[updated_at]', $searchModel->updated_at, ['class' => 'form-control dateandtime'])
                            ],
                            'status:boolean',

                            ['class' =>croacworks\essentials\components\gridview\ActionColumnCustom::class,'verGroup'=>false],
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
