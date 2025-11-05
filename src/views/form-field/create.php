<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\FormField */

$this->title = Yii::t('app', Yii::t('app', 'Create Form Field'));
$this->params['breadcrumbs'][] = ['label' =>  Yii::t('app', Yii::t('app', 'Form Fields')), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <?=$this->render('_form', [
                        'model' => $model
                    ]) ?>
                </div>
            </div>
        </div>
        <!--.card-body-->
    </div>
    <!--.card-->
</div>
