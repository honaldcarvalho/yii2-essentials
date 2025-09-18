<?php

use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\models\Folder;
use croacworks\essentials\widgets\AppendModel;
use croacworks\essentials\widgets\ListFiles;
use yii\helpers\Html;
use yii\widgets\DetailView;
use croacworks\essentials\widgets\StorageUploadMultiple;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\Folder */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Folders'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

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
                            'name',
                            'description',
                            'external:boolean',
                            'created_at:datetime',
                            'updated_at:datetime',
                            'status:boolean',
                        ],
                    ]) ?>

                    <?= AppendModel::widget([
                        'title' => Yii::t('app', 'Folders'),
                        'attactModel' => 'Folder',
                        'controller' => 'folder',
                        'attactClass' => 'croacworks\\essentials\\models\\Folder',
                        'dataProvider' => new \yii\data\ActiveDataProvider([
                            'query' => $model->getFolders(),
                        ]),
                        'showFields' => ['folder.name', 'folder.description', 'folder.status:boolean'],
                        'fields' =>
                        [
                            [
                                'name' => 'folder_id',
                                'type' => 'hidden',
                                'value' => $model->id
                            ],
                            [
                                'name' => 'name',
                                'type' => 'text',
                            ],
                            [
                                'name' => 'description',
                                'type' => 'text',
                            ],
                            [
                                'name' => 'status',
                                'type' => 'checkbox'
                            ],
                        ]
                    ]); ?>
                    
                    <?= StorageUploadMultiple::widget([
                        'folder_id' => $model->id,
                        'group_id' => 1,
                        'grid_reload' => 1,
                        'grid_reload_id' => '#list-files-grid'
                    ]); ?>

                    <?= ListFiles::widget([
                        'dataProvider' => $dataProvider,
                    ]); ?>

                </div>
                <!--.card-body-->
            </div>
            <!--.card-->
        </div>
        <!--.col-md-12-->
    </div>
    <!--.row-->
</div>