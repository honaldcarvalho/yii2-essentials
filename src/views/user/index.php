<?php

use croacworks\essentials\components\gridview\ActionColumnCustom;
use croacworks\essentials\models\User;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
/** @var yii\web\View $this */
/** @var app\models\UserSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Users');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Create User'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            'id',
            'group.name:text:'.Yii::t('app', 'Group'),
            'language.name:text:'.Yii::t('app', 'Language'),
            'theme',
            'username',
            'email:email',
            'created_at:datetime',
            'updated_at:datetime',
            [
                'attribute'=>'status',
                'value'=> function($model){
                    return $model->status == $model::STATUS_INACTIVE ? Yii::t('app','Inactive') : ( $model->status == $model::STATUS_NOSYSTEM ? Yii::t('app','No System User') : Yii::t('app','Active'));
                }
            ],
            [
                'class' => ActionColumnCustom::class
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>
