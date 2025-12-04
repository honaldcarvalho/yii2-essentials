<?php

use croacworks\essentials\widgets\FormResponseMetaWidget;
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\FormResponse $model */
/** @var croacworks\essentials\models\DynamicForm $formDef */

$this->title = Yii::t('app', 'Record #{id}', ['id' => $model->id]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', $model_name), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="container-fluid">
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><?= Html::encode($this->title) ?></h5>
      <div class="d-flex gap-2">
        <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
          'class' => 'btn btn-danger',
          'data' => ['confirm' => Yii::t('app', 'Are you sure you want to delete this item?'), 'method' => 'post'],
        ]) ?>
      </div>
    </div>

    <div class="card-body">
      <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
          'id',
          [
            'label' => Yii::t('app', 'Form'),
            'value' => $formDef->name,
          ],
          [
            'label' => Yii::t('app', 'Created at'),
            'value' => Yii::$app->formatter->asDatetime($model->created_at),
          ],
          [
            'label' => Yii::t('app', 'Updated at'),
            'value' => Yii::$app->formatter->asDatetime($model->updated_at),
          ],
        ],
      ]) ?>

    </div>
  </div>

  <?php
  echo FormResponseMetaWidget::widget([
    'formResponseId' => $model->id,
    // OR
    // 'dynamicFormId' => $dynamicFormId,
    // 'modelClass'    => common\models\Course::class,
    // 'modelId'       => $model->id,
    'title'          => Yii::t('app', $model_name),
    'card'           => true,
    'viewMode'       => 'list'

    // 'fileUrlCallback' => fn(int $id) => ['/storage/file/view','id'=>$id],
  ]);
  ?>

</div>