<?php

use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\widgets\ListFiles;
use croacworks\essentials\widgets\StorageUploadMultiple;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\Page */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Pages'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <p>
                        <?= \croacworks\essentials\widgets\DefaultButtons::widget(['model'=>$model]) ?>
                    </p>
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            'group.name:text:'.Yii::t('app', 'Group'),
                            'section.name:text:'.Yii::t('app', 'Page Section'),
                            'slug',
                            'title',
                            'description',
                            'content:raw',
                            'keywords:ntext',
                            'created_at:datetime',
                            'status:boolean',
                        ],
                    ]) ?>
                </div>
                <!--.col-md-12-->
            </div>
            <!--.row-->
        </div>
        <!--.card-body-->
    </div>
    <!--.card-->


    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= Yii::t('app', 'Add File'); ?></h3>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <?= StorageUploadMultiple::widget([
                        'group_id' => AuthorizationController::userGroup(),
                        'attact_model'=>[
                            'class_name'=> 'weebz\\yii2basics\\models\\PageFile',
                            'id'=> $model->id,
                            'fields'=> ['page_id','file_id']
                        ],
                        'grid_reload'=>1,
                        'grid_reload_id'=>'#list-files-grid'
                    ]); ?>
                </div>
            </div>
            <!--.row-->
        </div>
        <!--.card-->
    </div>
    <!--.card-->
    
    <?= ListFiles::widget([
        'dataProvider' => $dataProvider,
    ]); ?>
</div>