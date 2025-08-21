<?php

use croacworks\essentials\widgets\FileInputModel;
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\User $model */

$this->title = Yii::t('app', 'View User: {name}', [
    'name' => $model->fullname,
]);

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                
                <p>
                <?= Html::a(Yii::t('app', '<i class="fas fa-edit"></i>&nbsp; Edit'), ['edit', 'id' => $model->id], ['class' => 'btn btn-warning']) ?>
                </p>

                <div class="col-md-12">
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            [
                                'attribute'=>'file_id',
                                'format'=> 'raw',
                                'value'=> function($model){
                                    if($model->file === null) {
                                        return Yii::t('app', 'No image');
                                    }
                                    return Html::img($model->file->url, ['class'=>'img-fluid', 'style'=>'max-width: 200px;']);
                                }
                            ],
                            'fullname',
                            'username',
                            'email:email',
                            'phone',
                            'status:boolean',
                        ],
                    ]) ?>
                    
                </div>
            </div>
        </div>
        <!--.card-body-->
    </div>
    <!--.card-->
</div>
<!--.container-->
    