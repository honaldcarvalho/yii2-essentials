<?php

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\Group */

$this->title = Yii::t('app', 'Update Group: {name}', [
    'name' => $model->name,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Groups'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>


<div class="user-update">
    <?= $this->render('_form', [
        'model' => $model
    ]) ?>
</div>