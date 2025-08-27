<?php

use croacworks\essentials\components\gridview\ActionColumnCustom;
use croacworks\essentials\widgets\AppendModel;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\SysMenu */

$this->title = $model->label;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Menus'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

\yii\web\YiiAsset::register($this);

?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <p>
                        <?= croacworks\essentials\widgets\DefaultButtons::widget([
                            'controller' => Yii::$app->controller->id,
                            'model' => $model,
                            'verGroup' => false
                        ]) ?>
                    </p>
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            [
                                'attribute' => 'parent_id',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    if ($model->parent_id != null)
                                        return Html::a($model->menu->label, Url::toRoute([Yii::getAlias('@web/menu/view'), 'id' => $model->parent_id]));
                                }
                            ],
                            [
                                'attribute' => 'label',
                                'label' => 'Menu',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    /** @var \croacworks\essentials\models\SysMenu $model */
                                    $count = $model->getChildren()->count();
                                    $badge = $count ? " <span class='badge bg-secondary'>{$count}</span>" : '';
                                    return \yii\helpers\Html::a(
                                        \yii\helpers\Html::encode($model->label) . $badge,
                                        ['view', 'id' => $model->id]
                                    );
                                }
                            ],
                            'icon',
                            'visible',
                            'url:url',
                            'path',
                            'active',
                            'status:boolean',
                        ],
                    ]) ?>
                </div>
                <!--.col-md-12-->
            </div>
            <!--.row-->
        </div>
        <!--.card-body-->
    </div>
    <!--.card-->

    <?= AppendModel::widget([
        'title' => Yii::t('app', 'Folders'),
        'attactModel' => 'SysMenu',
        'controller' => 'menu',
        'attactClass' => 'croacworks\\essentials\\models\\SysMenu',
        'dataProvider' => new \yii\data\ActiveDataProvider([
            'query' => $model->getChildren(),
        ]),
        'order' => true,
        'orderModel' => 'SysMenu',
        'orderField' => 'order',
        'showFields' => [
            [
                'attribute' => 'label',
                'label' => 'Menu',
                'format' => 'raw',
                'value' => function ($model) {
                    /** @var \croacworks\essentials\models\SysMenu $model */
                    $count = $model->getChildren()->count();
                    $badge = $count ? " <span class='badge bg-secondary'>{$count}</span>" : '';
                    return \yii\helpers\Html::a(
                        \yii\helpers\Html::encode($model->label) . $badge,
                        ['view', 'id' => $model->id]
                    );
                }
            ],
            'icon',
            'order',
            'url:url',
            'status:boolean',
        ],
        'fields' =>
        [
            [
                'name' => 'parent_id',
                'type' => 'hidden',
                'value' => $model->id
            ],
        ]
    ]); ?>
</div>