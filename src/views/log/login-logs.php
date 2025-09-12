<?php

use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $username string */
/* @var $success string */

$this->title = Yii::t('app', 'Login Logs');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Logs'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$successOptions = [
    ''  => Yii::t('app', 'All'),
    '1' => Yii::t('app', 'Success'),
    '0' => Yii::t('app', 'Failure'),
];

// helper para decodificar com fallback
$parse = static function ($row) {
    $d = @json_decode($row->data, true);
    return is_array($d) ? $d : [];
};

?>

<div class="row">
  <div class="col-md-12">

    <div class="card mb-3">
      <div class="card-body">
        <form method="get" action="<?= Html::encode(Url::to(['login-logs'])) ?>" class="row g-2">
          <div class="col-sm-4">
            <label class="form-label"><?= Yii::t('app', 'Username') ?></label>
            <input type="text" name="username" value="<?= Html::encode($username) ?>" class="form-control" placeholder="<?= Yii::t('app', 'Search username…') ?>">
          </div>
          <div class="col-sm-3">
            <label class="form-label"><?= Yii::t('app', 'Status') ?></label>
            <select name="success" class="form-select">
              <?php foreach ($successOptions as $k => $label): ?>
                <option value="<?= Html::encode($k) ?>" <?= $success === (string)$k ? 'selected' : '' ?>><?= Html::encode($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-3 d-flex align-items-end">
            <button class="btn btn-primary me-2" type="submit"><?= Yii::t('app', 'Filter') ?></button>
            <a href="<?= Html::encode(Url::to(['login-logs'])) ?>" class="btn btn-secondary"><?= Yii::t('app', 'Clear') ?></a>
          </div>
        </form>
      </div>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-striped table-bordered table-sm'],
        'columns' => [
            [
                'attribute' => 'id',
                'headerOptions' => ['style' => 'width:90px'],
            ],
            [
                'label' => Yii::t('app', 'Username'),
                'format' => 'raw',
                'value' => function($row) use ($parse) {
                    $d = $parse($row);
                    $u = $d['username'] ?? null;
                    if ($u) {
                        return Html::encode($u);
                    }
                    // fallback: se houve user_id, mostra o username da relação
                    return Html::encode($row->user->username ?? '');
                },
            ],
            [
                'label' => Yii::t('app', 'Result'),
                'format' => 'raw',
                'value' => function($row) use ($parse) {
                    $d = $parse($row);
                    $ok = (bool)($d['success'] ?? false);
                    $class = $ok ? 'badge bg-success' : 'badge bg-danger';
                    $text  = $ok ? Yii::t('app', 'Success') : Yii::t('app', 'Failure');
                    return Html::tag('span', $text, ['class' => $class]);
                },
                'headerOptions' => ['style' => 'width:120px'],
                'contentOptions' => ['class' => 'text-center'],
            ],
            [
                'label' => Yii::t('app', 'Reason'),
                'format' => 'ntext',
                'value' => function($row) use ($parse) {
                    $d = $parse($row);
                    return (string)($d['reason'] ?? '');
                },
            ],
            [
                'attribute' => 'ip',
                'headerOptions' => ['style' => 'width:150px'],
            ],
            [
                'attribute' => 'device',
                'headerOptions' => ['style' => 'width:160px'],
            ],
            [
                'attribute' => 'created_at',
                'format' => ['datetime'],
                'headerOptions' => ['style' => 'width:200px'],
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {delete}',
                'urlCreator' => function ($action, $model) {
                    return [$action, 'id' => $model->id];
                },
            ],
        ],
    ]); ?>

  </div>
</div>
