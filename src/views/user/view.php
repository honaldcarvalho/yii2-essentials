<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\User $model */

$this->title = Yii::t('app', 'User: #{id} - {name}', ['id' => $model->id,'name' => $model->username]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="user-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= croacworks\essentials\widgets\DefaultButtons::widget(['controller' => 'User','model'=>$model]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'group.name:text:'.Yii::t('app', 'Group'),
            #'language.name:text:'.Yii::t('app', 'Language'),
            #'theme',
            [
                'attribute'=>'file_id',
                'header' => 'Preview',
                'format' => 'raw',
                'value' => function ($model) {
                    if ($model->profile->file) {
                        return Html::a("<img class='brand-image img-circle elevation-3' width='50' src='{$model->profile->file->url}' />",
                        Yii::getAlias('@web').$model->profile->file->url,
                        ['class'=>'btn btn-outline-secondary',"data-fancybox "=>"", "title"=>\Yii::t('app','View')]);
                    }
                }
            ],
            'username',
            'email:email',
            'created_at:datetime',
            'updated_at:datetime',
            [
                'attribute'=>'status',
                'value'=> function($model){
                    return $model->status == $model::STATUS_INACTIVE ? Yii::t('app','Inactive') : ( $model->status == $model::STATUS_ACTIVE ? Yii::t('app','Active') : Yii::t('app','Deleted') );
                }
            ]
        ],
    ]) ?>

</div>
