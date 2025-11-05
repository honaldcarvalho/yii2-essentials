<?php

use croacworks\essentials\widgets\DefaultButtons;
use croacworks\essentials\widgets\FileInput;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\custom\ServiceOrigin */

$this->title = $model->fullname;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Client'), 'url' => ['index']];
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
                            <?= croacworks\essentials\widgets\DefaultButtons::widget(['model' => $model])?>
                        </div>
                    </div>

                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            'nickname',
                            'fullname',
                            'email:email',
                            'phone',
                            'identity_number',
                            'cpf_cnpj',
                            'city_id',
                            'street',
                            'district',
                            'number',
                            'postal_code',
                            'username',
                            'address_complement',
                            'notes',
                            'created_at:datetime',
                            'updated_at:datetime',
                            [
                                'attribute' => 'status',
                                'value' => function ($model) {
                                    return $model->status == $model::STATUS_INACTIVE ? Yii::t('app', 'Inactive') : ($model->status == $model::STATUS_DISABLED ? Yii::t('app', 'Disabled') : Yii::t('app', 'Active'));
                                }
                            ]
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
