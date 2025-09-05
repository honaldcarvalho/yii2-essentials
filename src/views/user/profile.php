<?php
/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\User */
/* @var $profile croacworks\essentials\models\UserProfile */

use yii\helpers\Html;

$this->title = Yii::t('app', 'Update User: {name}', ['name' => $model->username]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->username, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="user-update">
    <h1><?= Html::encode($this->title) ?></h1>
    <?= $this->render('_form_profile', [
        'model' => $model,
        'profile' => $profile,
    ]) ?>
</div>
