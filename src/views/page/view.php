<?php

use croacworks\essentials\widgets\FormResponseMetaWidget;
use croacworks\essentials\widgets\ListFiles;
use croacworks\essentials\widgets\StorageUploadMultiple;
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\Page $model */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', $model_name), 'url' => ['index']];
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
                            'group.name:text:' . Yii::t('app', 'Group'),
                            'pageSection.name:text:' . Yii::t('app', 'Page Section'),
                            [
                                'attribute' => 'file_id',
                                'format' => 'raw',
                                'value' => function ($data) {
                                    if (!empty($data->file_id) && $data->file !== null) {
                                        $url = Yii::getAlias('@web') . $data->file->urlThumb;
                                        return "<img class='brand-image img-circle elevation-3' width='150' src='{$url}' />";
                                    }
                                }
                            ],
                            'slug',
                            'title',
                            'description',
                            //'content:raw',
                            'keywords:ntext',
                            'created_at:datetime',
                            'status:boolean',
                        ],
                    ]) ?>
                    <?= $hasDynamic ? FormResponseMetaWidget::widget([
                        'dynamicFormId' => $dynamicFormId,
                        'modelClass'    => $class,
                        'modelId'       => (int)$model->id,
                        'title'         => Yii::t('app','Page metadata'),
                        'card'          => true,
                        'fileUrlCallback' => fn(int $id) => ['/storage/file/view','id'=>$id],
                    ]) : '';
                    ?>
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
                <!--.card-body-->
            </div>
            <!--.card-->
        </div>
        <!--.col-md-12-->
    </div>
    <!--.row-->
</div>