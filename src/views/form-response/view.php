<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Form Responses'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <p>
                        <?= \weebz\yii2basics\widgets\DefaultButtons::widget([
                            'controller' => 'FormResponse',
                            'model' => $model,
                            'verGroup' => false
                        ]) ?>
                    </p>

                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            'dynamic_form_id',
                            [
                                'label' => Yii::t('app', 'Respostas'),
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
                        ],
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
</div>
