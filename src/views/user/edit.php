<?php
/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\User */
/* @var $profile croacworks\essentials\models\UserProfile */

use yii\helpers\Html;

$this->title = Yii::t('app', 'Edit User: {name}', ['name' => $model->profile->fullname]);
$this->params['breadcrumbs'][] = ['label' => $model->profile->fullname, 'url' => ['profile']];
$this->params['breadcrumbs'][] = Yii::t('app', 'Edit');
?>
<div class="user-update">
    <h1><?= Html::encode($this->title) ?></h1>
    <?= $this->render('_form_profile', [
        'model' => $model,
        'profile' => $profile,
    ]) ?>
</div>
