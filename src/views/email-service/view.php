<?php

use croacworks\essentials\widgets\DefaultButtons;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\EmailService */

$this->title = "{$model->id}#{$model->description}";
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Email Services'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            
            <h1><?= Html::encode($this->title) ?></h1>

            <div class="card">
                <div class="card-body">

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <?= croacworks\essentials\widgets\DefaultButtons::widget(['model' => $model,'verGroup' => false])?>
                        </div>
                    </div>
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            'description',
                            'scheme',
                            'enable_encryption:boolean',
                            'encryption',
                            'host',
                            'password:password',
                            'username',
                            'port',
                        ],
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