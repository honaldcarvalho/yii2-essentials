<?php

use croacworks\essentials\components\gridview\FormResponseGridView;
use yii\helpers\Html;
use yii\widgets\Pjax;


/** @var yii\web\View $this */
/** @var app\models\search\FormResponseSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var croacworks\essentials\models\DynamicForm $formDef */

$this->title = Yii::t('app', $model_name);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', $model_name), 'url' => ['index']];
?>

<div class="container-fluid">
  <div class="row">
    <div class="col-md-12">

      <h1><?= Html::encode($this->title) ?></h1>

      <div class="card">
        <div class="card-body">

          <div class="row mb-2">
            <div class="col-md-12">
              <?= croacworks\essentials\widgets\DefaultButtons::widget([
                'show' => ['create'],
                'buttons_name' => ['create' => Yii::t('app', 'Create') . ' ' . Yii::t('app', 'Page')]
              ]) ?>
            </div>
          </div>
          <?php echo $this->render('/_parts/filter', ['view' => '/page', 'searchModel' => $searchModel]); ?>
          <?php Pjax::begin(['id' => 'pjax-grid-responses']); ?>

          <?= FormResponseGridView::widget([
            'dataProvider' => $dataProvider,
            'dynamicForm'  => $formDef,
            'controller'   => 'teacher',
            // opcional: escolher campos e/ou limitar
            // 'visibleFields' => ['title','email','picture','attachments'],
            // 'limit' => 6,
            // mini-config de thumbs e exibição
            'cellOptions' => [
              // 'showEmpty' => false,
              'thumb' => ['w' => 72, 'h' => 72, 'fit' => 'cover'],
            ],
            'withSystemColumns' => true,
          ]) ?>

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