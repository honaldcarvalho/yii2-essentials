<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\File */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Files'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
$script = <<< JS
    $(function(){
        Fancybox.bind("[data-fancybox]");
    });
JS;
$this::registerJs($script,$this::POS_END);
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <p>
                        <?= croacworks\essentials\widgets\DefaultButtons::widget(['model'=>$model]) ?>
                    </p>
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            'name',
                            [
                                'attribute'=>'folder_id',
                                'format'=>'raw',
                                'value'=> function($model){
                                    if($model->folder_id != null)
                                        return Html::a($model->folder->name,Url::toRoute([Yii::getAlias('@web/folder/view'), 'id' => $model->folder_id]));
                                }
                            ],
                            'description',
                            'path',
                            'url:url',
                            'pathThumb',
                            [
                                'attribute'=>'urlThumb',
                                'header' => 'Preview',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    if ($model->urlThumb) {
                                        $url = Yii::getAlias('@web') . $model->urlThumb;
                                        return Html::a("<img class='brand-image img-circle elevation-3' width='50' src='{$url}' />",Yii::getAlias('@web').$model->url,
                                        ['class'=>'btn btn-outline-secondary',"data-fancybox "=>"", "title"=>\Yii::t('app','View')]);
                                    } else {
                                        return Html::a("<img class='brand-image img-circle elevation-3' width='50' src='/preview_square.jpg' />",Yii::getAlias('@web').$model->url,
                                        ['class'=>'btn btn-outline-secondary',"data-fancybox "=>"", "title"=>\Yii::t('app','View')]);
                                    }
                                }
                            ],
                            'extension',
                            'size:bytes',
                            'duration:time',
                            'created_at:datetime',
                            'updated_at:datetime',
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
</div>