<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\File */

$this->title = Yii::t('app', 'Create File');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Files'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$aspectRatio ='210/297';

if(Yii::$app->request->get('aspectRatio') !== null){
    $aspectRatio = Yii::$app->request->get('aspectRatio');
}

?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <?=$this->render('_form', [
                        'model' => $model,
                        'accept'=> $accept,
                        'aspectRatio'=> $aspectRatio
                    ]) ?>
                </div>
            </div>
        </div>
        <!--.card-body-->
    </div>
    <!--.card-->
</div>