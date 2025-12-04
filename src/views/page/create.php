<?php
/* @var $this yii\web\View */
/* @var $model app\models\Page */
/* @var $model_name string */
/* @var $dynamicForm \croacworks\essentials\models\DynamicForm */

$this->title = Yii::t('app', "Create {$model_name}");
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', $model_name), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('app', 'Create');
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
            'responseModel' => null,
            'hasDynamic' => $model::hasDynamic,
          ]) ?>

        </div>
      </div>
    </div>
  </div>
</div>