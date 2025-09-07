<?php

use croacworks\essentials\controllers\CommonController;
use croacworks\essentials\controllers\RoleController;
use croacworks\essentials\models\SysMenu;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\Menu */
/* @var $form yii\widgets\ActiveForm */

$assetsDir = CommonController::getAssetsDir();
$controllers = RoleController::getAllControllers();
$text_select_controller = Yii::t('app', 'Select a controller');

$script = <<< JS
$('#controller-select').select2({ width: '100%', placeholder: '$text_select_controller' });
JS;

$this->registerJs($script);

?>

<div class="row mt-2">
    <div class="col-md-12">

        <?php $form = ActiveForm::begin([
            'action' => ['index'],
            'method' => 'get',
        ]); ?>

        <?= $form->field($model, 'parent_id')->widget(\kartik\select2\Select2::classname(), [
            'data' => yii\helpers\ArrayHelper::map(SysMenu::find()
                ->asArray()->all(), 'id', 'label'),
            'options' => ['multiple' => false, 'placeholder' => Yii::t('app', 'Select Menu')],
            'pluginOptions' => [
                'allowClear' => true,
                'width' => '100%',
            ],
        ])->label('Menu');
        ?>

        <?= $form->field($model, 'controller')->dropDownList($controllers, [
            'id' => 'controller-select',
            'prompt' => '-- Selecione o controller --'
        ])->label('Controller') ?>

        <?= $form->field($model, 'label')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'status')->checkbox() ?>

        <div class="form-group">
            <?= Html::submitButton('<i class="fas fa-search  mr-2"></i>' . Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
            <?= Html::resetButton('<i class="fas fa-broom mr-2 btn-reset"></i>' . Yii::t('app', 'Reset'), ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
    <!--.col-md-12-->
</div>