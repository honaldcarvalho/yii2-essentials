<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\ReportTemplate $model */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Report Templates'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
$extra = [
    [
        'controller' => 'report-template',
        'action' => 'report-preview',
        'icon' => '<i class="fas fa-eye mr-3"></i>',
        'text' => Yii::t('app', 'Preview Template'),
        'link' => \yii\helpers\Url::to(['report-template/preview', 'id' => $model->id]),
        'options' =>                    [
            'id' => 'btn-preview',
            'class' => 'btn btn-default btn-block-m',
            'data-fancybox' => '',
            'data-type' => "iframe",
            'data-custom-class' => "fancybox-iframe",
            'data-src'=>\yii\helpers\Url::to(['report-template/preview', 'id' => $model->id,'fakeData'=>1])
        ],
    ]
];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            
            <h1><?= Html::encode($this->title) ?></h1>

            <div class="card">
                <div class="card-body">

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <?= croacworks\essentials\widgets\DefaultButtons::widget(['model' => $model,'extras'=>$extra])?>
                        </div>
                    </div>

                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
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
                        ],
                    ]) ?>

                </div>
                <!--.card-body-->
            </div>
            <!--.card-->
        </div>
        <!--.col-md-12-->
    </div>
    <!--.row-->
</div>