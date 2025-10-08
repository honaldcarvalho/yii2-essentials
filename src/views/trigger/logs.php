<?php
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\Pjax;

$this->title = 'Trigger Logs — ' . $model->name;
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-3">
                <h5><?= Html::encode($this->title) ?></h5>
                <?= Html::a('← Back', ['index'], ['class' => 'btn btn-secondary']) ?>
            </div>

            <?php Pjax::begin(['id' => 'logs-grid']); ?>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'columns' => [
                    'executed_at:datetime',
                    'model_class',
                    'model_id',
                    [
                        'attribute' => 'success',
                        'format' => 'boolean',
                    ],
                    'message',
                ],
            ]) ?>
            <?php Pjax::end(); ?>
        </div>
    </div>
</div>
