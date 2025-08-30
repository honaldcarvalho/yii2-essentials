<?php

use yii\widgets\DetailView;
use croacworks\essentials\models\User;
use croacworks\essentials\widgets\AppendModel;
use yii\bootstrap5\Html;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\Group */

$this->title = Yii::t('app', 'View Group: {name}', [
    'name' => $model->name,
]);

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Groups'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

$buttons = [
    // [
    //     'controller' => 'user',
    //     'action' => 'create',
    //     'icon' => '<i class="fas fa-plus-square mr-2"></i>',
    //     'text' => Yii::t('app', 'Add User'),
    //     'link' => "/user/create?id={$model->id}",
    //     'options' =>                    [
    //         'class' => 'btn btn-success btn-block-m',
    //     ],
    // ],
    [
        'controller' => 'role',
        'action' => 'apply-templates',
        'icon' => '<i class="fas fa-plus-square mr-2"></i>',
        'text' => Yii::t('app', 'Add Roles'),
        'link' => "/role/apply-templates?group_id={$model->id}",
        'options' =>                    [
            'class' => 'btn btn-outline-success btn-block-m',
        ],
    ],
    [
        'controller' => 'role',
        'action' => 'apply-templates',
        'icon' => '<i class="fas fa-minus-square mr-2"></i>',
        'text' => Yii::t('app', 'Remove Roles'),
        'link' => "/role/remove-templates?group_id={$model->id}&reseed=1",
        'options' =>                    [
            'class' => 'btn btn-outline-danger btn-block-m',
        ]
    ],
];
?>
<div class="user-update">
    <h1><?= Html::encode($this->title) ?></h1>
    <p>
        <?= croacworks\essentials\widgets\DefaultButtons::widget(['controller' => 'Group','model'=>$model,'extras'=>$buttons])  ?>
    </p>
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'parente.name:text:'.Yii::t('app', 'Parent'),
            'level',
            'name',
            'status:boolean',
        ],
    ]) ?>

    <?= AppendModel::widget([
        'title' => Yii::t('app', 'Users'),
        'attactModel' => 'UserGroup',
        'uniqueId' => 'UserAppend',
        'controller' => 'configuration',
        'template' => '{edit}{remove}',
        'attactClass' => 'croacworks\\essentials\\models\\UserGroup',
        'dataProvider' => new \yii\data\ActiveDataProvider([
            'query' => $model->getUserGroups(),
        ]),
        'showFields' => [
            'user.profile.fullname',
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
                'value' => yii\helpers\ArrayHelper::map(User::find()->select(['id', "concat(username,' - ',email) as name"])->asArray()->all(), 'id', 'name'),
                'type' => 'select2'
            ],

        ]
    ]); ?>


    <?= AppendModel::widget([
        'new_button'=> false,
        'title' => Yii::t('app', 'Roles'),
        'attactModel' => 'Role',
        'uniqueId' => 'rolesAppend',
        'controller' => 'roles',
        'template' => '',
        'attactClass' => 'croacworks\\essentials\\models\\Role',
        'dataProvider' => new \yii\data\ActiveDataProvider([
            'query' => $model->getRoles(),
        ]),
        'showFields' => [
            'id',
            'user.fullname:text:'.Yii::t('app', 'User'),
            'group.name:text:'.Yii::t('app', 'Role'),
            'controller',
            [
                'attribute'=>'actions',
                'value'=> function($data){
                    return str_replace(';', ' | ', $data->actions);
                }
            ],
            'origin',
            'status:boolean'
        ]
    ]); ?>
</div>