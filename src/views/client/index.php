<?php

use croacworks\essentials\components\ActionColumn;
use croacworks\essentials\components\gridview\ActionColumnCustom;
use croacworks\essentials\widgets\DefaultButtons;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\custom\ServiceOriginSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Clients');
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
                                'buttons_name' => ['create' => Yii::t('app', 'Create') . ' ' . Yii::t('app', 'Client')]
                             ])?>
                        </div>
                    </div>
                    <?php echo $this->render('@vendor/croacworks/yii2-essentials/src/views/_parts/filter', ['view' =>'/client','searchModel' => $searchModel]); 
                    ?>

                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'columns' => [

                            'group.name:text',
                            //'picture',
                            'email:email',
                            'fullname',
                            'username',
                            'phone',
                            //'cpf_cnpj',
                            //'auth_key',
                            //'password',
                            //'password_reset_token',
                            //'verification_token',
                            'status:boolean',
                            'created_at:datetime',
                            'updated_at:datetime',

                            [
                                'class' => ActionColumnCustom::class
                            ],
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