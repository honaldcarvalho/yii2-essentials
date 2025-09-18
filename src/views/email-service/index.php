<?php

use croacworks\essentials\widgets\DefaultButtons;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel croacworks\essentials\models\EmailServiceSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Email Services');
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
                                'buttons_name' => ['create' => Yii::t('app', 'Create') . ' ' . Yii::t('app', 'Email Service'),'verGroup' => false]
                             ])?>
                        </div>
                    </div>

                    <?php echo $this->render('/_parts/filter', ['view' =>'/email-service','searchModel' => $searchModel]); ?>

                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'columns' => [
                            'description',
                            'scheme',
                            'enable_encryption:boolean',
                            'encryption',
                            'host',
                            //'username',
                            //'password',
                            //'port',

                            ['class' =>'croacworks\essentials\components\gridview\ActionColumnCustom',],
                        ],
                        'summaryOptions' => ['class' => 'summary mb-2'],
                        'pager' => [
                            'class' => 'yii\bootstrap5\LinkPager',
                        ]
                    ]); ?>

                </div>
                <!--.card-body-->
            </div>
            <!--.card-->
        </div>
        <!--.col-md-12-->
    </div>
    <!--.row-->
</div>