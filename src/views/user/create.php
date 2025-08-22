<?php
/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\User */
/* @var $profile croacworks\essentials\models\UserProfile */

use yii\helpers\Html;

$this->title = Yii::t('app', 'Create User');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-create">
    <h1><?= Html::encode($this->title) ?></h1>
    <?= $this->render('_form', [
        'model' => $model,
        'profile' => $profile,
    ]) ?>
</div>
