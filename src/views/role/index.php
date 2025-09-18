<?php

use croacworks\essentials\models\Group;;
use croacworks\essentials\models\Role;
use croacworks\essentials\models\User;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
/** @var yii\web\View $this */
/** @var croacworks\essentials\models\RoleSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Roles');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            
            <h1><?= yii\bootstrap5\Html::encode($this->title) ?></h1>

            <div class="card">
                <div class="card-body">

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <?= croacworks\essentials\widgets\DefaultButtons::widget([
                                'show' => ['index'],
                                'buttons_name' => ['index' => Yii::t('app', 'List') . ' ' . Yii::t('app', 'Roles'),'verGroup'=>false]
                            ]) ?>
                        </div>
                    </div>

                    <?php Pjax::begin(); ?>
                    <?php echo $this->render('/_parts/filter', ['view' =>'/role','searchModel' => $searchModel]); ?>

                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
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
                                'class' =>'croacworks\essentials\components\gridview\ActionColumnCustom','verGroup'=>false,
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