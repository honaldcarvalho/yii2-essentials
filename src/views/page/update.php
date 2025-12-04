<?php
/* @var $this yii\web\View */
/* @var $model app\models\Page */
/* @var $model_name string */
/* @var $dynamicForm \croacworks\essentials\models\DynamicForm */
/* @var $dynamicModel \yii\base\DynamicModel */

use yii\helpers\Url;

$this->title = Yii::t('app', "Update {$model_name}: {name}", ['name' => $model->title]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', $model_name), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');

?>

<div class="container-fluid">
  <div class="card">
    <div class="card-body">
      <div class="row">
        <div class="col-md-12">
          <?= $this->render($viewName, [
            'model'         => $model,
            'model_name'    => $model_name,
            'dynamicForm'   => $dynamicForm,
            'formResponse' => $formResponse,
            'hasDynamic' => $model::hasDynamic,
          ]) ?>
        </div>
      </div>
    </div>
  </div>
</div>