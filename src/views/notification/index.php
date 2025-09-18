<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\Pjax;
use croacworks\essentials\models\Notification;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;

$this->title = Yii::t('app', 'Notificações');
$config = [
  'csrfToken'     => Yii::$app->request->getCsrfToken(),
  'readUrl'       => Url::to(['read']),
  'deleteUrl'     => Url::to(['delete']),
  'deleteAllUrl'  => Url::to(['delete-all']),
  'pjaxContainer' => '#pjax-notifications',
];

$this->registerJsVar('notifConfig', $config, View::POS_HEAD);

// expõe config com escaping seguro (JSON + HTML-safe)
$this->registerJs(
  'window.notifConfig = ' . Json::htmlEncode($config) . ';',
  View::POS_HEAD
);

$js = <<<JS
(function(){
  var cfg           = window.notifConfig || {};
  var csrfToken     = cfg.csrfToken;
  var readUrl       = cfg.readUrl;
  var deleteUrl     = cfg.deleteUrl;
  var deleteAllUrl  = cfg.deleteAllUrl;
  var pjaxContainer = cfg.pjaxContainer || '#pjax-notifications';
  var inFlight      = false; // avoid double reload

  function confirmSwal(msg) {
    if (typeof Swal === 'undefined') {
      return Promise.resolve( confirm(msg) ? { isConfirmed: true } : { isConfirmed: false } );
    }
    return Swal.fire({
      title: msg,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: yii.t('app','Yes'),
      cancelButtonText: yii.t('app','Cancel')
    });
  }

  function postJson(url, payload) {
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {'Content-Type':'application/json','X-CSRF-Token': csrfToken},
      body: JSON.stringify(payload || {})
    }).then(function(r){ return r.json(); });
  }

  function safeReload() {
    if (inFlight) return;
    inFlight = true;
    $.pjax.reload({container: pjaxContainer, timeout: 0, scrollTo: false})
      .always(function(){ inFlight = false; });
  }

  function updateHeaderBadge(count) {
    var badge = document.getElementById('notif-badge');
    if (!badge) return;
    var n = Number(count || 0);
    badge.textContent = n;
    badge.style.display = n > 0 ? 'inline-block' : 'none';
  }

  // Event delegation (works after PJAX)
  document.addEventListener('click', function(ev){
    var t = ev.target;

    // 3.1) Mark all as read
    if (t.id === 'btn-mark-all-read') {
      ev.preventDefault();
      confirmSwal(yii.t('app','Mark ALL notifications as read?')).then(function(res){
        if (!res.isConfirmed) return;
        postJson(readUrl, {all: 1})
          .then(function(resp){
            if (resp && (resp.ok === true || typeof resp.count !== 'undefined')) {
              if (typeof Swal !== 'undefined') Swal.fire(yii.t('app','Done!'), yii.t('app','All marked as read.'), 'success');
              updateHeaderBadge(resp.count);
              safeReload();
            } else {
              if (typeof Swal !== 'undefined') Swal.fire(yii.t('app','Oops'), yii.t('app','Could not mark as read.'), 'error');
            }
          })
          .catch(function(){
            if (typeof Swal !== 'undefined') Swal.fire(yii.t('app','Oops'), yii.t('app','Network error.'), 'error');
          });
      });
      return;
    }

    // 3.2) Delete read
    if (t.id === 'btn-delete-read') {
      ev.preventDefault();
      confirmSwal(yii.t('app','Delete ALL read notifications?')).then(function(res){
        if (!res.isConfirmed) return;
        postJson(deleteAllUrl, {onlyRead: 1})
          .then(function(resp){
            if (resp && resp.ok) {
              if (typeof Swal !== 'undefined') Swal.fire(yii.t('app','Done!'), yii.t('app','Read notifications deleted.'), 'success');
              safeReload();
            } else {
              if (typeof Swal !== 'undefined') Swal.fire(yii.t('app','Oops'), yii.t('app','Could not delete.'), 'error');
            }
          })
          .catch(function(){
            if (typeof Swal !== 'undefined') Swal.fire(yii.t('app','Oops'), yii.t('app','Network error.'), 'error');
          });
      });
      return;
    }

    // 3.3) Delete all
    if (t.id === 'btn-delete-all') {
      ev.preventDefault();
      confirmSwal(yii.t('app','Delete ALL notifications (read and unread)?')).then(function(res){
        if (!res.isConfirmed) return;
        postJson(deleteAllUrl, {})
          .then(function(resp){
            if (resp && resp.ok) {
              if (typeof Swal !== 'undefined') Swal.fire(yii.t('app','Done!'), yii.t('app','All notifications deleted.'), 'success');
              updateHeaderBadge(0);
              safeReload();
            } else {
              if (typeof Swal !== 'undefined') Swal.fire(yii.t('app','Oops'), yii.t('app','Could not delete.'), 'error');
            }
          })
          .catch(function(){
            if (typeof Swal !== 'undefined') Swal.fire(yii.t('app','Oops'), yii.t('app','Network error.'), 'error');
          });
      });
      return;
    }

    // 3.4) Delete one
    if (t.closest && t.closest('.js-notif-delete')) {
      ev.preventDefault();
      var btn = t.closest('.js-notif-delete');
      var id = btn.getAttribute('data-id');
      if (!id) return;
      confirmSwal(yii.t('app','Delete this notification?')).then(function(res){
        if (!res.isConfirmed) return;
        postJson(deleteUrl + '?id=' + encodeURIComponent(id), {})
          .then(function(resp){
            if (resp && resp.ok) {
              if (typeof Swal !== 'undefined') Swal.fire(yii.t('app','Done!'), yii.t('app','Notification deleted.'), 'success');
              safeReload();
            } else {
              if (typeof Swal !== 'undefined') Swal.fire(yii.t('app','Oops'), yii.t('app','Could not delete.'), 'error');
            }
          })
          .catch(function(){
            if (typeof Swal !== 'undefined') Swal.fire(yii.t('app','Oops'), yii.t('app','Network error.'), 'error');
          });
      });
      return;
    }
  });
})();
JS;

$this->registerJs($js, View::POS_END);
?>

<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between w-100">
    <div>
      <h5 class="mb-0"><?= Html::encode($this->title) ?></h5>
      <div class="small text-muted"><?= Yii::t('app', 'Manage your notifications') ?></div>
    </div>
    <div class="d-flex gap-2">
      <?= Html::button(Yii::t('app', 'Mark all as read'), ['class' => 'btn btn-outline-primary', 'id' => 'btn-mark-all-read', 'type' => 'button']) ?>
      <?= Html::button(Yii::t('app', 'Delete read'),       ['class' => 'btn btn-outline-secondary', 'id' => 'btn-delete-read', 'type' => 'button']) ?>
      <?= Html::button(Yii::t('app', 'Delete all'),        ['class' => 'btn btn-outline-danger', 'id' => 'btn-delete-all', 'type' => 'button']) ?>
    </div>
  </div>

  <div class="card-body">

    <div class="row g-2 mb-3">
      <div class="col-auto">
        <form id="filter-form" class="d-flex align-items-center gap-2" method="get">
          <label class="form-label mb-0"><?= Yii::t('app', 'Status') ?></label>
          <select name="status" class="form-select form-select-sm" onchange="document.getElementById('filter-form').submit()">
            <option value=""><?= Yii::t('app', 'Todos') ?></option>
            <option value="<?= Notification::STATUS_UNREAD ?>" <?= Yii::$app->request->get('status', '') === (string)Notification::STATUS_UNREAD ? 'selected' : '' ?>>
              <?= Yii::t('app', 'Não lidas') ?>
            </option>
            <option value="<?= Notification::STATUS_READ ?>" <?= Yii::$app->request->get('status', '') === (string)Notification::STATUS_READ ? 'selected' : '' ?>>
              <?= Yii::t('app', 'Lidas') ?>
            </option>
          </select>

          <label class="form-label mb-0"><?= Yii::t('app', 'Tipo') ?></label>
          <input type="text" class="form-control form-control-sm" name="type"
            value="<?= Html::encode(Yii::$app->request->get('type', '')) ?>"
            placeholder="<?= Yii::t('app', 'ex.: system') ?>" />
          <button type="submit" class="btn btn-sm btn-primary"><?= Yii::t('app', 'Filtrar') ?></button>
        </form>
      </div>
    </div>

    <?php \yii\widgets\Pjax::begin([
      'id' => 'pjax-notifications',
      'timeout' => 0,
      'enablePushState' => false,
      'clientOptions' => ['scrollTo' => false],
    ]); ?>

    <?= GridView::widget([
      'dataProvider' => $dataProvider,
      'tableOptions' => ['class' => 'table table-hover align-middle'],
      'layout' => "{items}\n<div class='d-flex justify-content-between align-items-center mt-2'>{summary}{pager}</div>",
      'columns' => [
        [
          'attribute' => 'status',
          'label' => Yii::t('app', 'Status'),
          'format' => 'raw',
          'contentOptions' => ['style' => 'width:110px'],
          'value' => function (Notification $m) {
            $isUnread = (int)$m->status === Notification::STATUS_UNREAD;
            $badge = $isUnread ? 'bg-primary' : 'bg-secondary';
            $text  = $isUnread ? Yii::t('app', 'Não lida') : Yii::t('app', 'Lida');
            return "<span class='badge {$badge}'>{$text}</span>";
          }
        ],
        [
          'attribute' => 'description',
          'label' => Yii::t('app', 'Title'),
          'format' => 'raw',
          'value' => function (Notification $m) {
            $title = Html::encode($m->description);
            $content = $m->content ? '<div class="small text-muted">' . Html::encode($m->content) . '</div>' : '';
            return "<div class='fw-semibold'>{$title}</div>{$content}";
          }
        ],
        [
          'attribute' => 'type',
          'label' => Yii::t('app', 'Type'),
          'contentOptions' => ['style' => 'width:140px'],
        ],
        [
          'attribute' => 'created_at',
          'label' => Yii::t('app', 'Data'),
          'contentOptions' => ['style' => 'width:180px'],
          'value' => function (Notification $m) {
            return Yii::$app->formatter->asDatetime($m->created_at);
          }
        ],
        [
          'label' => Yii::t('app', 'Link'),
          'format' => 'raw',
          'contentOptions' => ['style' => 'width:120px'],
          'value' => function (Notification $m) {
            if (!$m->url) return '<span class="text-muted">—</span>';
            $u = Html::encode($m->url);
            return Html::a(Yii::t('app', 'Open'), $u, ['class' => 'btn btn-sm btn-outline-primary', 'target' => '_self']);
          }
        ],
        // ActionColumn
        [
          'class' => \yii\grid\ActionColumn::class,
          'template' => '{delete}',
          'contentOptions' => ['style' => 'width:80px', 'class' => 'text-end'],
          'buttons' => [
            'delete' => function ($url, \croacworks\essentials\models\Notification $model) {
              return \yii\helpers\Html::button(
                '<i class="cil-trash"></i> ' . Yii::t('app', 'Remove'),
                [
                  'class'   => 'btn btn-sm btn-outline-danger js-notif-delete',
                  'data-id' => $model->id,
                  'type'    => 'button',
                ]
              );
            },
          ],
        ],

      ],
    ]); ?>

    <?php Pjax::end(); ?>
  </div>
</div>