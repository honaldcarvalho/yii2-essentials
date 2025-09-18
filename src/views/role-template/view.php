<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\RoleTemplateController $model */

$this->title = "{$model->level}: $model->controller";
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Roles Templates'), 'url' => ['index']];
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
                            'level',
                            'controller',
                            [
                                'attribute'=>'actions',
                                'value'=> function($data){
                                    return str_replace(';', ' | ', $data->actions);
                                }
                            ],
                            'origin',
                            'status:boolean',
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