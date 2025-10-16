<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

$urlParams = $generator->generateUrlParams();

echo "<?php\n";
?>

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model <?= ltrim($generator->modelClass, '\\') ?> */

$this->title = $model-><?= $generator->getNameAttribute() ?>;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', '<?= Inflector::pluralize(Inflector::camel2words(StringHelper::basename($generator->modelClass))) ?>'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">

            <h1><?= "<?= Html::encode(\$this->title) ?>" ?></h1>

            <div class="card">
                <div class="card-body">

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <?= "<?= croacworks\\essentials\\widgets\\DefaultButtons::widget(['model' => \$model]) ?>" ?>
                        </div>
                    </div>

                    <?= "<?= DetailView::widget([
                        'model' => \$model,
                        'attributes' => [" ?>

<?php
if (($tableSchema = $generator->getTableSchema()) === false) {
    foreach ($generator->getColumnNames() as $name) {
        echo "                            '" . $name . "',\n";
    }
} else {
    foreach ($generator->getTableSchema()->columns as $column) {
        $format = $generator->generateColumnFormat($column);
        echo "                            '" . $column->name . ($format === 'text' ? "" : ":" . $format) . "',\n";
    }
}
?>
                    <?= "        ],
                    ]) ?>" ?>

                </div>
                <!--.card-body-->
            </div>
            <!--.card-->

        </div>
        <!--.col-md-12-->
    </div>
    <!--.row-->
</div>
