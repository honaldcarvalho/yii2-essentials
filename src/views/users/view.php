<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\User $model */

$this->title = Yii::t('app', 'View User: {name}', [
    'name' => $model->fullname,
]);

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-12">
                    <?= croacworks\essentials\widgets\DefaultButtons::widget(['controller' => 'User','model'=>$model,'verGroup'=>false]) ?>
                </div>
            </div>

            <div class="row">

                <div class="col-md-12">
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            [
                                'attribute'=>'file_id',
                                'format'=> 'raw',
                                'value'=> function($model){
                                    if($model->file === null) {
                                        return Yii::t('app', 'No image');
                                    }
                                    return Html::img($model->file->url, ['class'=>'img-fluid', 'style'=>'max-width: 200px;']);
                                }
                            ],
                            'fullname',
                            'username',
                            'email:email',
                            'phone',
                            //'password',
                            //'access_token',
                            //'token_expires',
                            //'auth_key',
                            //'reset_token',
                            [
                                'attribute'=>'status',
                                'value'=> function($model){
                                    return $model->status == $model::STATUS_INACTIVE ? Yii::t('app','Inactive') : ( $model->status == $model::STATUS_ACTIVE ? Yii::t('app','Active') : Yii::t('app','Deleted') );
                                }
                            ]
                        ],
                    ]) ?>
                    
                </div>
            </div>
        </div>
        <!--.card-body-->
    </div>
    <!--.card-->
    
    <!-- GROUP -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">GROUPs</h3>
                </div>
                
                <?php if (Yii::$app->session->hasFlash('error')): ?>
                    <div class="alert alert-danger alert-dismissable">
                         <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                         <h4><i class="fas fa-exclamation-triangle"></i> Erro</h4>
                         <?= Yii::$app->session->getFlash('error') ?>
                    </div>
                <?php endif; ?>
                
                <div class="card-body">

                    <?php $form = \yii\bootstrap5\ActiveForm::begin(['action'=>'/user/add-group']); ?>
                        <?= $form->field($user_group, 'group_id')->widget(\kartik\select2\Select2::classname(), [
                                'data' => $groups_free_arr,
                                'options' => ['multiple' => true, 'placeholder' => 'Selecione as groups'],
                                'pluginOptions' => [
                                    'allowClear' => true
                                ],
                            ])->label('GROUP');
                        ?>

                        <?= $form->field($user_group, 'user_id')->hiddenInput(['value'=>$model->id])->label(FALSE) ?>

                        <div class="form-group">
                            <?= Html::submitButton('Adicionar', ['class' => 'btn btn-warning']) ?>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        </div>

                    <?php \yii\bootstrap5\ActiveForm::end(); ?>
 
                    <?=\yii\grid\GridView::widget([
                        'dataProvider' => $groups,
                        'tableOptions' => [
                                 'id' => 'groups-user',
                                 'class'=>'table table-striped table-bordered'
                         ],
                        'rowOptions'=>['class'=>'grabbable'],
                        'columns' => [
                            'group.name',
                            ['class' => \croacworks\essentials\components\gridview\ActionColumn::className(),
                                'template' => '{delete}'  ,
                                'buttons' => [  
                                    'delete' => function ($url, $model, $key) {
                                        return Html::a('<i class="fas fa-trash"></i>', yii\helpers\Url::to(['/user/remove-group', 'id' => $model->id]));
                                    }    
                                ]
                            ],
                        ],
                    ]); ?>
                    
                </div>
                <!--.card-body-->
            </div>
            <!--.card-->
        </div>
        <!--.col-md-12-->
    </div>
</div>