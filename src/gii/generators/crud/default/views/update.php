<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

$urlParams = $generator->generateUrlParams();
$modelClassName = Inflector::camel2words(StringHelper::basename($generator->modelClass));
$nameAttribute = $generator->getNameAttribute();

echo "<?php\n";
?>

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model <?= ltrim($generator->modelClass, '\\') ?> */

$this->title = Yii::t('app', 'Update <?= $modelClassName ?>: {name}', [
    'name' => $model-><?= $nameAttribute ?>,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', '<?= Inflector::pluralize($modelClassName) ?>'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model-><?= $nameAttribute ?>, 'url' => ['view', <?= $urlParams ?>]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">

            <h1><?= "<?= \\yii\\bootstrap5\\Html::encode(\$this->title) ?>" ?></h1>

            <div class="card">
                <div class="card-body">

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <?= "<?= croacworks\\essentials\\widgets\\DefaultButtons::widget([
                                'show' => ['index'],
                                'buttons_name' => [
                                    'index' => Yii::t('app', 'List') . ' ' . Yii::t('app', '" . Inflector::pluralize(Inflector::camel2words(StringHelper::basename($generator->modelClass))) . "')
                                ]
                            ]) ?>" ?>
                        </div>
                    </div>

                    <?= "<?= \$this->render('_form', [
                        'model' => \$model
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
