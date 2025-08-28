<?php

use weebz\yii2basics\models\Group;;
use weebz\yii2basics\models\Role;
use weebz\yii2basics\models\User;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
/** @var yii\web\View $this */
/** @var weebz\yii2basics\models\RoleSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Roles');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-12">
                            <?= weebz\yii2basics\widgets\DefaultButtons::widget([
                                'controller' => 'Role','show' => ['create'],
                                'buttons_name' => ['create' => Yii::t('app', 'Create Role')],
                                'verGroup'=>false
                                ])?>
                        </div>
                    </div>

                    <?php Pjax::begin(); ?>
                    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        'columns' => [
                            'id',
                            [   
                                'attribute'=>'user_id',
                                'filter'=> \kartik\select2\Select2::widget([
                                            'attribute' => 'user_id',
                                            'name'=>'RoleSearch[user_id]',
                                            'value'=>$searchModel->user_id,
                                            'data' => yii\helpers\ArrayHelper::map(User::find()->asArray()->all(),'id','fullname'),
                                            'options' => ['placeholder' => 'Select User'],
                                            'pluginOptions' => [
                                                'allowClear' => true
                                            ],
                                        ]),
                                'value'=> function($data){return isset($data->user)?$data->user->fullname:'';}
                            ],
                            [   
                                'attribute'=>'group_id',
                                'filter'=> \kartik\select2\Select2::widget([
                                            'attribute' => 'group_id',
                                            'name'=>'RoleSearch[group_id]',
                                            'value'=>$searchModel->group_id,
                                            'data' => yii\helpers\ArrayHelper::map(Group::find()->asArray()->all(),'id','name'),
                                            'options' => ['placeholder' => 'Select Group'],
                                            'pluginOptions' => [
                                                'allowClear' => true
                                            ],
                                        ]),
                                'value'=> function($data){return isset($data->group)?$data->group->name:'';}
                            ],
                            'controller',
                            [
                                'attribute'=>'actions',
                                'value'=> function($data){
                                    return str_replace(';', ' | ', $data->actions);
                                }
                            ],
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
                                'class' =>'weebz\yii2basics\components\gridview\ActionColumn','verGroup'=>false,
                                'urlCreator' => function ($action, Role $model, $key, $index, $column) {
                                    return Url::toRoute([$action, 'id' => $model->id]);
                                 }
                            ],
                        ],
                    ]); ?>

                    <?php Pjax::end(); ?>

                </div>
                <!--.card-body-->
            </div>
            <!--.card-->
        </div>
        <!--.col-md-12-->
    </div>
    <!--.row-->
</div>