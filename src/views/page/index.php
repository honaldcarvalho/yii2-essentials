<?php

use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\grid\GridView;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\Page $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', $model_name);
$this->params['breadcrumbs'][] = $this->title;

$js = <<<JS
document.addEventListener('click', function (e) {
  const target = e.target.closest('.copy-url-link');
  if (!target) return;
  const url = target.getAttribute('data-url');
  if (!url) return;
  navigator.clipboard.writeText(url).then(() => {
    toastr.success('URL copiada!', '', {timeOut: 2000});
  }).catch(() => {
    toastr.error(yii.t('app','Error copying URL'));
  });
});
JS;
$this->registerJs($js);

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
                'buttons_name' => [
                  'create' => Yii::t('app', 'Create') . ' ' . Yii::t('app', 'Page'),
                ],
                'controller' => 'pages',
              ]) ?>
            </div>
          </div>

          <?php Pjax::begin(['timeout' => 8000]); ?>

          <?php echo $this->render('/_parts/filter', ['view' => '/page', 'searchModel' => $searchModel]); ?>

          <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => [
              'id',
              'language.name:text:' . Yii::t('app', 'Language'),
              //'model_group_id',
              [
                'attribute' => 'title',
                'format' => 'raw',
                'value' => function ($model) {
                  return Html::a(Html::encode($model->title), ['update', 'id' => $model->id]);
                }
              ],
              'slug',
              [
                'attribute' => 'status',
                'value' => fn($m) => (int)$m->status === 1 ? Yii::t('app', 'Active') : Yii::t('app', 'Inactive'),
              ],
              [
                'class' => croacworks\essentials\components\gridview\ActionColumnCustom::class,
                'template' => '{status} {view} {update} {remove}',
              ],
            ],
          ]) ?>

          <?php Pjax::end(); ?>

        </div>
      </div>

    </div>
  </div>
</div>