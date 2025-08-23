<?php

use croacworks\essentials\models\User;
use croacworks\essentials\widgets\AppendModel;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\Group */

$this->title = Yii::t('app', 'View Group: {name}', [
    'name' => $model->name,
]);

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Groups'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

$buttons[] =
    [
        'controller' => 'user',
        'action' => 'create',
        'icon' => '<i class="fas fa-plus-square mr-2"></i>',
        'text' => Yii::t('app', 'Add User'),
        'link' => "/user/create?id={$model->id}",
        'options' =>                    [
            'class' => 'btn btn-success btn-block-m',
        ],
    ];

?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <p>
                        <?php //croacworks\essentials\widgets\DefaultButtons::widget(['controller' => 'Group','model'=>$model,'extras'=>$buttons,'verGroup'=>false]) 
                        ?>
                    </p>
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            'name',
                            'status:boolean',
                        ],
                    ]) ?>
                </div>

                <?= AppendModel::widget([
                    'title' => Yii::t('app', 'Meta Tags'),
                    'attactModel' => 'UserGroup',
                    'uniqueId' => 'UserAppend',
                    'controller' => 'configuration',
                    'template' => '{edit}{remove}',
                    'attactClass' => 'croacworks\\essentials\\models\\UserGroup',
                    'dataProvider' => new \yii\data\ActiveDataProvider([
                        'query' => $model->getUserGroups(),
                    ]),
                    'showFields' => [
                        'user.fullname',
                        'user.email',
                        [
                            'attribute' => 'user.created_at',
                            'format' => 'date',
                            'label' => Yii::t('app', 'Created At'),
                        ],
                        [
                            'attribute' => 'user.updated_at',
                            'format' => 'date',
                            'label' => Yii::t('app', 'Updated At'),
                        ],
                        'user.status:boolean',
                    ],
                    'fields' =>
                    [
                        [
                            'name' => 'group_id',
                            'type' => 'hidden',
                            'value' => $model->id
                        ],
                        [
                            'name' => 'user_id',
                            'valeu' => User::find()->select(['id', "concat(username,' - ',email) as name"])->where(['status' => 1])->asArray()->all(),
                            'type' => 'select2'
                        ],
    
                    ]
                ]); ?>
            </div>
            <!--.row-->
        </div>
        <!--.card-body-->
    </div>
    <!--.card-->
</div>