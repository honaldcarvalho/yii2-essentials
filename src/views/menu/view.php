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

$script = <<< JS

    $(function(){

        Fancybox.bind("[data-fancybox]");

        jQuery("#list-item-menu .table tbody").sortable({
            update: function(event, ui) {
                let items  = [];
                let i = 0;
                $('#overlay').show();
                $( "#list-item-menu .table tbody tr" ).each(function( index ) {
                    items[items.length] = $( this ).attr("data-key");
                });
                
                $.ajax({
                    method: "POST",
                    url: '/menu/order-menu',
                    data: {'items':items}
                }).done(function(response) {        
                    toastr.success("atualizado");
                }).fail(function (response) {
                    toastr.error("Error ao atualizar a ordem. Recarregue a pagina");
                }).always(function (response) {
                    $('#overlay').hide();
                });

            }
        });

    });
  
JS;

$this::registerJs($script, $this::POS_END);

// $MP = new MercadoPago('TEST-3935825493019834-122811-a58b6ebfb2ce4572be4dec4a221a1f2c-25239504');
// // echo "ADD PAYMENT\n";
// dd($MP->addPayment());
// // echo "VER PAYMENTs\n";
// //dd($MP->getPayments());
// die();
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
                            'label',
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
        'attactModel' => 'Folder',
        'controller' => 'folder',
        'attactClass' => 'croacworks\\essentials\\models\\Folder',
        'dataProvider' => new \yii\data\ActiveDataProvider([
            'query' => $model->getChildren(),
        ]),
        'showFields' => [
            [
                'attribute' => 'label',

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