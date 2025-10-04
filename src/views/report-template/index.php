<?php

use croacworks\essentials\components\gridview\ActionColumnCustom;
use croacworks\essentials\models\ReportTemplate;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
/** @var yii\web\View $this */
/** @var croacworks\essentials\models\ReportTemplateSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Report Templates');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            
            <h1><?= Html::encode($this->title) ?></h1>

            <div class="card">
                <div class="card-body">

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <?= croacworks\essentials\widgets\DefaultButtons::widget([
                                'show' => ['create'],
                                'buttons_name' => ['create' => Yii::t('app', 'Create') . ' ' . Yii::t('app', 'Report Template')]
                             ])?>
                        </div>
                    </div>

                    <?php Pjax::begin(); ?>
                    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        'columns' => [
                            'id',
                            'group_id',
                            'name',
                            'description:ntext',
                            //'header_html:ntext',
                            //'footer_html:ntext',
                            //'body_html:ntext',
                            'status:boolean',
                            'created_at:datetime',
                            'updated_at:datetime',
                            [
                                'class' => ActionColumnCustom::class,
                                'template' => '{view} {update} {delete} {preview}',
                                'buttons' => [
                                    'preview' => function ($url, $model) {
                                        return \yii\helpers\Html::a(
                                            '<i class="fas fa-eye"></i>',
                                            ['preview', 'id' => $model->id],
                                            [
                                                'class' => 'btn btn-sm btn-outline-primary',
                                                'title' => 'Preview Template',
                                                'target' => '_blank'
                                            ]
                                        );
                                    },
                                ],
                            ],
                        ],
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
