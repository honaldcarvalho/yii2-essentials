<?php

use croacworks\essentials\components\gridview\ActionColumnCustom;
use croacworks\essentials\models\RoleTemplateController;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
/** @var yii\web\View $this */
/** @var croacworks\essentials\models\RolesTemplateS $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Roles Templates');
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
                                'buttons_name' => ['create' => Yii::t('app', 'Create') . ' ' . Yii::t('app', 'Role Template')]
                             ])?>
                        </div>
                    </div>

                    <?php Pjax::begin(); ?>
                    <?php echo $this->render('/_parts/filter', ['view' =>'/role-template','searchModel' => $searchModel]); ?>

                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'columns' => [
                            ['class' => 'yii\grid\SerialColumn'],

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
                            [
                                'class' => ActionColumnCustom::class
                            ],
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