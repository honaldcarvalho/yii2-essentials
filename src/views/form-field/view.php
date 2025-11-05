<?php

use croacworks\essentials\enums\FormField;
use croacworks\essentials\enums\FormFieldType;
use croacworks\essentials\widgets\AppendModel;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\FormField */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Form Fields'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <p>
                        <?= \croacworks\essentials\widgets\DefaultButtons::widget(['controller' => 'FormField', 'model' => $model, 'verGroup' => false]) ?>                    </p>
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            'dynamic_form_id',
                            'label',
                            'name',
                            'type',
                            'default',
                            'model_class',
                            'model_field',
                            'order',
                            'status:boolean',
                        ],
                    ]) ?>
                </div>
                <!--.col-md-12-->
            </div>
            <!--.row-->
        </div>
        <!--.card-body-->
    </div>
    <!--.card-->

</div>