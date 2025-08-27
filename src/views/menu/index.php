<?php

use yii\grid\GridView;
use croacworks\essentials\widgets\DefaultButtons;
use croacworks\essentials\components\gridview\ActionColumnCustom;
use croacworks\essentials\controllers\AuthController;
use croacworks\essentials\controllers\RoleController;
use yii\bootstrap5\Html;

/* @var $this yii\web\View */
/* @var $searchModel croacworks\essentials\models\MenuSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Menus');
$this->params['breadcrumbs'][] = $this->title;

$script = <<< JS

  $('#submit-auto-add').on('click', function() {
    const controller = $('#controller').val().trim();
    const action = $('#action').val().trim() || 'index';

    if (!controller) {
        toastr.error('Informe o controller.');
        return;
    }

    $('#submit-auto-add').prop('disabled', true);

    $.ajax({
        url: '/menu/auto-add',
        method: 'GET',
        data: { controller, action },
        success: function(response) {
            location.reload();
        },
        error: function(xhr) {
            const msg = xhr.responseText || 'Erro ao adicionar menu.';
            toastr.error(msg);
        },
        complete: function() {
            $('#submit-auto-add').prop('disabled', false);
            $('#modal-auto-add').modal('hide');
        }
    });
});

JS;
$controllers = RoleController::getAllControllers(); // FQCNs
$this::registerJs($script, $this::POS_END);

?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-12">
                            <div class="btn-group">
                                <?= croacworks\essentials\widgets\DefaultButtons::widget([
                                    'controller' => Yii::$app->controller->id,
                                    'show' => ['create'],
                                    'verGroup' => false
                                ]) ?>

                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-auto-add">
                                    <i class="fas fa-plus-circle"></i> Adicionar Automático
                                </button>
                            </div>
                        </div>
                    </div>

                    <?php // echo $this->render('_search', ['model' => $searchModel]); 
                    ?>

                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel'  => $searchModel,
                        'columns' => [
                            [
                                'attribute' => 'label',
                                'label' => 'Menu',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    /** @var \croacworks\essentials\models\SysMenu $model */
                                    $count = $model->getChildren()->count();
                                    $badge = $count ? " <span class='badge bg-secondary'>{$count}</span>" : '';
                                    return \yii\helpers\Html::a(
                                        \yii\helpers\Html::encode($model->label) . $badge,
                                        ['view', 'id' => $model->id]
                                    );
                                }
                            ],
                            'icon',
                            'order',
                            'url:url',
                            'status:boolean',
                            [
                                'class' => croacworks\essentials\components\gridview\ActionColumnCustom::class,
                                'template' => '{status} {view} {update} {delete}',
                                'uniqueId' => 'menu',
                                'order' => true,
                                'orderModel' => 'SysMenu',
                                'orderField' => 'order',
                            ],
                        ],
                        'summaryOptions' => ['class' => 'summary mb-2'],
                        'pager' => ['class' => 'yii\bootstrap5\LinkPager'],
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


<div class="modal fade" id="modal-auto-add" tabindex="-1" aria-labelledby="modalAutoAddLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAutoAddLabel">Adicionar Menu Automático</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="auto-add-form">
                    <div class="mb-3">
                        <label for="controller" class="form-label">Controller (FQCN)</label>
                        <?= Html::dropDownList('controller', null, $controllers, [
                            'id' => 'controller',
                            'prompt' => '-- Selecione o controller --',
                            'class' => 'form-select'
                        ]) ?>
                        <div class="form-text">Ex: <code>app\controllers\ClientController</code></div>
                    </div>
                    <div class="mb-3">
                        <label for="action" class="form-label">Action</label>
                        <input type="text" class="form-control" id="action" name="action" value="index" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="submit-auto-add">Adicionar</button>
            </div>
        </div>
    </div>
</div>