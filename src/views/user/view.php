<?php

use croacworks\essentials\controllers\AuthorizationController;
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\User $model */

$this->title = Yii::t('app', 'User: #{id} - {name}', ['id' => $model->id, 'name' => $model->username]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

\yii\web\YiiAsset::register($this);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">

            <h1><?= Html::encode($this->title) ?></h1>

            <div class="card">
                <div class="card-body">

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <?= croacworks\essentials\widgets\DefaultButtons::widget(['model' => $model]) ?>
                        </div>
                    </div>
                    
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            'group.name:text:' . Yii::t('app', 'Group'),
                            'profile.fullname:text:' . Yii::t('app', 'Full Name'),
                            'username',
                            'email:email',
                            'profile.language.name:text:' . Yii::t('app', 'Language'),
                            #'theme',
                            [
                                'attribute' => 'file_id',
                                'header' => 'Preview',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    if ($model->profile->file) {
                                        return Html::a(
                                            "<img class='brand-image img-circle elevation-3' width='50' src='{$model->profile->file->url}' />",
                                            Yii::getAlias('@web') . $model->profile->file->url,
                                            ['class' => 'btn btn-outline-secondary', "data-fancybox " => "", "title" => \Yii::t('app', 'View')]
                                        );
                                    }
                                }
                            ],
                            'created_at:datetime',
                            'updated_at:datetime',
                            [
                                'attribute' => 'status',
                                'value' => function ($model) {
                                    return $model->status == $model::STATUS_INACTIVE ? Yii::t('app', 'Inactive') : ($model->status == $model::STATUS_ACTIVE ? Yii::t('app', 'Active') : Yii::t('app', 'Deleted'));
                                }
                            ]
                        ],
                    ]) ?>

                </div>
                <!--.card-body-->
            </div>
            <!--.card-->
        </div>
        <!--.col-md-12-->
    </div>
    <!--.row-->
</div>