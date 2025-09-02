<?php

use croacworks\essentials\components\gridview\ActionColumnCustom;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var app\models\Ucroacworks\essentials\ */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Groups');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="group-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <p>
        <?= Html::a(Yii::t('app', 'Create Group'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <?php echo $this->render('/_parts/filter', ['view' =>'/group','searchModel' => $searchModel]); 
    ?>
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