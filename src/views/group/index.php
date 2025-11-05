<?php

use croacworks\essentials\components\gridview\ActionColumnCustom;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\Ucroacworks\essentials\ */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Groups');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            
            <h1><?= Html::encode($this->title) ?></h1>

            <div class="card">
                <div class="card-body">

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <?= croacworks\essentials\widgets\DefaultButtons::widget([
                                'show' => ['create'],
                                'buttons_name' => ['create' => Yii::t('app', 'Create') . ' ' . Yii::t('app', 'Group')]
                             ])?>
                        </div>
                    </div>

                    <?php echo $this->render('/_parts/filter', ['view' =>'/group','searchModel' => $searchModel]); ?>
                    <?php Pjax::begin(); ?>


                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'columns' => [
                            'id',
                            'parent.name:text:'.Yii::t('app', 'Parent'),
                            'level',
                            'name',
                            'status:boolean',
                            ['class' => 'croacworks\essentials\components\gridview\ActionColumnCustom'],
                        ],
                    ]); ?>

                    <?php Pjax::end(); ?>


                </div>
                <!--.card-body-->
            </div>
            <!--.card-->
        </div>
        <!--.col-md-12-->
    </div>
    <!--.row-->
</div>
