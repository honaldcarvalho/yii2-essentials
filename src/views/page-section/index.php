<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $searchModel croacworks\essentials\models\PageSection */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Page Sections');
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
                                'buttons_name' => ['create' => Yii::t('app', 'Create') . ' ' . Yii::t('app', 'Page Section')]
                             ])?>
                        </div>
                    </div>

                    <?php echo $this->render('/_parts/filter', ['view' =>'/page-section','searchModel' => $searchModel]); ?>

                    <?php Pjax::begin(); ?>

                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'columns' => [
                            'id',
                            'pageSection.name:text:'.Yii::t('app', 'Page Section'),
                            [
                                'label' => Yii::t('app', 'Section Slug'),
                                'value' => function ($model) {
                                    return $model->page_section_id ? ($model->pageSection->slug ?? null) : null;
                                },
                            ],
                            'name',
                            'status:boolean',

                            ['class' => 'croacworks\essentials\components\gridview\ActionColumnCustom'],
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