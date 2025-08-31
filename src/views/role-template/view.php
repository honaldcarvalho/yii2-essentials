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
<div class="roles-template-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= croacworks\essentials\widgets\DefaultButtons::widget([
            'controller' => 'RoleTemplate',
            'model'      => $model
        ]) ?>
    </p>

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
