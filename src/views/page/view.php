<?php

use croacworks\essentials\widgets\ListFiles;
use croacworks\essentials\widgets\StorageUploadMultiple;
use yii\bootstrap5\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\Page */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Pages'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

<div class="user-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= croacworks\essentials\widgets\DefaultButtons::widget(['model' => $model]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'group.name:text:' . Yii::t('app', 'Group'),
            'section.name:text:' . Yii::t('app', 'Page Section'),
            'slug',
            'title',
            'description',
            'content:raw',
            'keywords:ntext',
            'created_at:datetime',
            'status:boolean',
        ],
    ]) ?>

    <?= StorageUploadMultiple::widget([
        'attach_model' => [
            'class_name' => \croacworks\essentials\models\PageFile::class,
            'id' => $model->id,
            'fields' => ['page_id', 'file_id']
        ],
        'grid_reload' => 1,
        'grid_reload_id' => '#list-files-grid'
    ]); ?>

    <?= ListFiles::widget([
        'dataProvider' => new \yii\data\ActiveDataProvider([
            'query' => $model->getFiles(),
        ]),
    ]); ?>
    
</div>