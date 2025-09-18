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
    <?= $this->render('_form', [
        'model' => $model,
        'profile' => $profile,
    ]) ?>
</div>


<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            
            <h1><?= yii\bootstrap5\Html::encode($this->title) ?></h1>

            <div class="card">
                <div class="card-body">

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <?= croacworks\essentials\widgets\DefaultButtons::widget([
                                'show' => ['index'],
                                'buttons_name' => ['index' => Yii::t('app', 'List') . ' ' . Yii::t('app', 'Users')]
                            ]) ?>
                        </div>
                    </div>

                    <?=$this->render('_form', [
                        'model' => $model,
                        'profile' => $profile
                    ]) ?>

                </div>
                <!--.card-body-->
            </div>
            <!--.card-->
        </div>
        <!--.col-md-12-->
    </div>
    <!--.row-->
</div>
