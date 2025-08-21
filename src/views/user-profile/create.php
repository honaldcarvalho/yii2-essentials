<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\UserProfile $model */

$this->title = Yii::t('app', 'Create User Profile');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'User Profiles'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-profile-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
