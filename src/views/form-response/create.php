<?php
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\FormResponse $model */
/** @var croacworks\essentials\models\DynamicForm $formDef */

$this->title = Yii::t('app', 'Create');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', $model_name), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><?= Html::encode($this->title) ?> — <?= Html::encode($formDef->name) ?></h5>
  </div>
  <div class="card-body">
    <?= $this->render('_form', ['model' => $model]) ?>
  </div>
</div>
