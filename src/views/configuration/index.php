<?php

use yii\helpers\Html;
use yii\grid\GridView;
use croacworks\essentials\components\gridview\ActionColumnCustom;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $searchModel croacworks\essentials\models\ParamSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Configuration');
$this->params['breadcrumbs'][] = $this->title;

$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->getCsrfToken();

$js = <<<JS
(function(){
  function swalLoading(title){
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: title || yii.t('app','Processing...'),
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
      });
    } else {
      console.log('[Clone] ' + yii.t('app','Loading...'));
    }
  }

  function swalError(msg){
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'error',
        title: yii.t('app','Error'),
        text: msg || yii.t('app','Operation failed.')
      });
    } else {
      alert(msg || yii.t('app','Error'));
    }
  }

  function swalSuccessAndGo(msg, url){
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'success',
        title: yii.t('app','Success'),
        text: msg || yii.t('app','Completed.')
      }).then(() => { if (url) window.location.href = url; });
    } else {
      if (url) window.location.href = url;
    }
  }

  document.addEventListener('click', async function(ev){
    const el = ev.target.closest('.action-clone');
    if (!el) return;

    ev.preventDefault();
    const url = el.getAttribute('data-url');
    if (!url) return;

    swalLoading(yii.t('app','Cloning configuration...'));

    try {
      const form = new FormData();
      form.append('{$csrfParam}', '{$csrfToken}');
      const res = await fetch(url, {
        method: 'POST',
        body: form,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });

      const isJson = (res.headers.get('content-type') || '').includes('application/json');
      if (!isJson) {
        window.location.reload();
        return;
      }

      const data = await res.json();

      if (data && data.ok) {
        swalSuccessAndGo(data.message || yii.t('app','Configuration cloned successfully.'), data.redirectUrl);
      } else {
        swalError((data && data.message) ? data.message : yii.t('app','Unknown error while cloning.'));
      }
    } catch (e) {
      swalError(e && e.message ? e.message : yii.t('app','Network error while cloning.'));
    }
  }, false);
})();
JS;

$this->registerJs($js, View::POS_END);
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
                'buttons_name' => ['create' => Yii::t('app', 'Create') . ' ' . Yii::t('app', 'Configuration')]
              ]) ?>
            </div>
          </div>

          <?php echo $this->render('/_parts/filter', ['view' => '/configuration', 'searchModel' => $searchModel]); ?>

          <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => [
              'id',
              'description',
              //'file_id',
              //'meta_viewport',
              //'meta_author',
              //'meta_robots',
              //'meta_googlebot',
              //'meta_keywords',
              //'meta_description',
              //'canonical',
              'host',
              'title',
              [
                'attribute' => 'file_id',
                'format' => 'raw',
                'value' => function ($data) {
                    if (!empty($data->file_id) && $data->file !== null) {
                        $url = Yii::getAlias('@web') . $data->file->urlThumb;
                        return "<img class='brand-image img-circle elevation-3' width='50' src='{$url}' />";
                    }
                }
              ],
              //'bussiness_name',
              'email:email',
              [
                'attribute' => 'created_at',
                'format' => 'date',
                'label' => Yii::t('app', 'Created At'),
                'filter' => Html::input('date', ucfirst(Yii::$app->controller->id) . 'Search[created_at]', $searchModel->created_at, ['class' => 'form-control dateandtime'])
              ],
              [
                'attribute' => 'updated_at',
                'format' => 'date',
                'label' => Yii::t('app', 'Updated At'),
                'filter' => Html::input('date', ucfirst(Yii::$app->controller->id) . 'Search[updated_at]', $searchModel->updated_at, ['class' => 'form-control dateandtime'])
              ],
              'status:boolean',
              [
                'class' => 'croacworks\essentials\components\gridview\ActionColumnCustom',
                'template' => '{clone} {view} {update} {delete}',
              ]
            ],
            'summaryOptions' => ['class' => 'summary mb-2'],
            'pager' => [
              'class' => 'yii\bootstrap5\LinkPager',
            ]
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