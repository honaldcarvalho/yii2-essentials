<?php

use croacworks\essentials\components\gridview\ActionColumnCustom;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var app\models\Ucroacworks\essentials\ */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Users');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <p>
        <?= croacworks\essentials\widgets\DefaultButtons::widget(['show' => ['create'],'buttons_name' => ['create' => Yii::t('app', 'New User')],])?>
    </p>
    <?php Pjax::begin(); ?>
    <?php echo $this->render('/_parts/filter', ['view' =>'/user','searchModel' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'id',
            'group.name:text:'.Yii::t('app', 'Group'),
            'profile.fullname:text:'.Yii::t('app', 'Full Name'),
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
            'email:email',
            'created_at:datetime',
            'updated_at:datetime',
            [
                'attribute'=>'status',
                'value'=> function($model){
                    return $model->status == $model::STATUS_INACTIVE ? Yii::t('app','Inactive') : ( $model->status == $model::STATUS_ACTIVE ? Yii::t('app','Active') : Yii::t('app','Deleted') );
                }
            ],
            [
                'class' => ActionColumnCustom::class
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>