<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

$urlParams = $generator->generateUrlParams();
$nameAttribute = $generator->getNameAttribute();

echo "<?php\n";
?>

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
<?= !empty($generator->searchModelClass) ? "/* @var \$searchModel " . ltrim($generator->searchModelClass, '\\') . " */\n" : '' ?>
/* @var \$dataProvider yii\\data\\ActiveDataProvider */

$this->title = Yii::t('app', '<?= Inflector::pluralize(Inflector::camel2words(StringHelper::basename($generator->modelClass))) ?>');
$this->params['breadcrumbs'][] = \$this->title;
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">

            <h1><?= "<?= Html::encode(\$this->title) ?>" ?></h1>

            <div class="card">
                <div class="card-body">

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <?= "<?= croacworks\\essentials\\widgets\\DefaultButtons::widget([
                                'show' => ['create'],
                                'buttons_name' => [
                                    'create' => Yii::t('app', 'Create') . ' ' . Yii::t('app', '" . Inflector::camel2words(StringHelper::basename($generator->modelClass)) . "')
                                ]
                            ]) ?>" ?>
                        </div>
                    </div>

<?php if (!empty($generator->searchModelClass)): ?>
                    <?= "<?php echo \$this->render('/_parts/filter', ['view' => '/" . Inflector::camel2id(StringHelper::basename($generator->modelClass)) . "', 'searchModel' => \$searchModel]); ?>" ?>
<?php endif; ?>

                    <?= "<?php Pjax::begin(); ?>" ?>


                    <?= "<?= GridView::widget([
                        'dataProvider' => \$dataProvider," ?>

<?php if (!empty($generator->searchModelClass)): ?>
                        <?= "'filterModel' => \$searchModel," ?>
<?php endif; ?>
                        <?= "'columns' => [
                            'id',\n"; ?>

<?php
$count = 0;
if (($tableSchema = $generator->getTableSchema()) === false) {
    foreach ($generator->getColumnNames() as $name) {
        if (++$count < 6) {
            echo "                            '" . $name . "',\n";
        } else {
            echo "                            //'" . $name . "',\n";
        }
    }
} else {
    foreach ($tableSchema->columns as $column) {
        $format = $generator->generateColumnFormat($column);
        if (++$count < 6) {
            echo "                            '" . $column->name . ($format === 'text' ? "" : ":" . $format) . "',\n";
        } else {
            echo "                            //'" . $column->name . ($format === 'text' ? "" : ":" . $format) . "',\n";
        }
    }
}
?>

                            ['class' => 'croacworks\essentials\components\gridview\ActionColumnCustom'],
                        ],
                        'summaryOptions' => ['class' => 'summary mb-2'],
                        'pager' => [
                            'class' => 'yii\bootstrap5\LinkPager',
                        ]
                    ]); ?>" ?>


                    <?= "<?php Pjax::end(); ?>" ?>


                </div>
                <!--.card-body-->
            </div>
            <!--.card-->

        </div>
        <!--.col-md-12-->
    </div>
    <!--.row-->
</div>
