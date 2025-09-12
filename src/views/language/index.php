<?php

use yii\grid\GridView;
use croacworks\essentials\widgets\DefaultButtons;

/* @var $this yii\web\View */
/* @var $searchModel croacworks\essentials\models\LanguageSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Languages');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-12">
                        <?= DefaultButtons::widget(
                            [
                                'controller' => Yii::$app->controller->id,
                                'show'=>['create'],'buttons_name'=>['create'=>Yii::t("backend","Create Language")],
                                'verGroup'=>false
                            ]) ?>
                        </div>
                    </div>


                    <?php echo $this->render('/_parts/filter', ['view' =>'/group','searchModel' => $searchModel]); ?>

                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'columns' => [

                            'id',
                            'code',
                            'name',
                            'status:boolean',

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
