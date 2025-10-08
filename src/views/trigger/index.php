<?php
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\Pjax;

$this->title = Yii::t('app', 'Triggers');
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-3">
                <h5><?= Html::encode($this->title) ?></h5>
                <?= Html::a(Yii::t('app', 'Create Trigger'), ['create'], ['class' => 'btn btn-primary']) ?>
            </div>

            <?php Pjax::begin(['id' => 'trigger-grid']); ?>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'columns' => [
                    'id',
                    'name',
                    'model_class',
                    'action_type',
                    'action_target',
                    [
                        'attribute' => 'enabled',
                        'format' => 'boolean',
                    ],
                    'last_triggered_at:datetime',
                    [
                        'class' => 'croacworks\essentials\components\gridview\ActionColumnCustom',
                        'template' => '{update} {logs} {delete}',
                        'buttons' => [
                            'logs' => function ($url, $model) {
                                return Html::a('<i class="fas fa-list"></i>', ['logs', 'id' => $model->id], [
                                    'title' => 'Logs',
                                    'class' => 'btn btn-sm btn-outline-secondary',
                                ]);
                            },
                        ],
                    ],
                ],
            ]); ?>
            <?php Pjax::end(); ?>
        </div>
    </div>
</div>
