<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

$this->title = Yii::t('app', 'Form Responses');
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
                                'controller' => 'FormResponse',
                                'show' => ['create'],
                                'buttons_name' => ['create' => Yii::t('app','Create Form Response')],
                                'verGroup' => true,
                            ]) ?>
                        </div>
                    </div>

                    <?php Pjax::begin(); ?>
                        <?= GridView::widget([
                            'dataProvider' => $dataProvider,
                            'filterModel' => $searchModel,
                            'columns' => [
                                ['class' => 'yii\grid\SerialColumn'],

                                'id',
                                'dynamic_form_id',
                                [
                                    'attribute' => 'response_data',
                                    'format' => 'ntext',
                                    'value' => function($model) {
                                        $data = $model->getData();
                                        return implode("\n", array_map(
                                            fn($k, $v) => "$k: $v",
                                            array_keys($data),
                                            $data
                                        ));
                                    }
                                ],
                                'created_at',
                                'updated_at',

                                ['class' => 'weebz\yii2basics\components\gridview\ActionColumn'],
                            ],
                            'summaryOptions' => ['class' => 'summary mb-2'],
                            'pager' => ['class' => 'yii\bootstrap5\LinkPager'],
                        ]); ?>
                    <?php Pjax::end(); ?>

                </div>
            </div>
        </div>
    </div>
</div>
