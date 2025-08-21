<?php

use croacworks\essentials\components\gridview\ActionColumn;
use croacworks\essentials\models\User;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\UserSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Users');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <p>
                        <?= croacworks\essentials\widgets\DefaultButtons::widget(['controller' => 'User','show'=>['create'],'buttons_name'=>['create'=>'Create User']]) ?>
                    </p>
                    
                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        'columns' => [
                            'id',
                            'fullname',
                            'username',
                            'email:email',
                            //'password',
                            //'access_token',
                            //'token_expires',
                            //'auth_key',
                            //'reset_token',
                            [
                                'attribute'=>'created_at',
                                'format' => 'date',
                                'label' => Yii::t('app', 'Created At'),
                                'filter' =>Html::input('date', ucfirst(Yii::$app->controller->id).'Search[created_at]',$searchModel->created_at,['class'=>'form-control dateandtime'])
                            ],
                            [
                                'attribute'=>'updated_at',
                                'format' => 'date',
                                'label' => Yii::t('app', 'Updated At'),
                                'filter' =>Html::input('date',ucfirst(Yii::$app->controller->id).'Search[updated_at]',$searchModel->updated_at,['class'=>'form-control dateandtime'])
                            ],
                            'status:boolean',
                            [
                                'class' =>'croacworks\essentials\components\gridview\ActionColumn',
                /*                 'urlCreator' => function ($action, User $model, $key, $index, $column) {
                                    return Url::toRoute([$action, 'id' => $model->id]);
                                 } */
                            ],
                        ],
                    ]); ?>

                </div>
            </div>
        </div>
        <!--.card-body-->
    </div>
    <!--.card-->
</div>