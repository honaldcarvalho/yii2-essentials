<?php

use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\grid\GridView;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\Page $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Pages';
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-12">
                            <?= croacworks\essentials\widgets\DefaultButtons::widget(['show' => ['create'], 'buttons_name' => ['create' => Yii::t('app', 'New User')],]) ?>
                        </div>
                    </div>


                    <?php Pjax::begin(); ?>
                    <?php echo $this->render('/_parts/filter', ['view' => '/page', 'searchModel' => $searchModel]); ?>

                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'columns' => [
                            'id',
                            'group.name:text:' . Yii::t('app', 'Group'),
                            'pageSection.name:text:' . Yii::t('app', 'Page Section'),
                            'slug',
                            'title',
                            'description',
                            //'content:ntext',
                            //'keywords:ntext',
                            [
                                'attribute' => 'created_at',
                                'format' => 'date',
                                'label' => Yii::t('app', 'Created at'),
                                'filter' => Html::input('date', ucfirst(Yii::$app->controller->id) . 'Search[created_at]', $searchModel->created_at, ['class' => 'form-control dateandtime'])
                            ],
                            [
                                'attribute' => 'updated_at',
                                'format' => 'date',
                                'label' => Yii::t('app', 'Updated at'),
                                'filter' => Html::input('date', ucfirst(Yii::$app->controller->id) . 'Search[updated_at]', $searchModel->updated_at, ['class' => 'form-control dateandtime'])
                            ],
                            'status:boolean',
                            [
                                'label' => Yii::t('app', 'Public URL'),
                                'format' => 'raw',
                                'value' => function ($model) {
                                    $group = (int)($model->group_id ?: 1);

                                    // tenta pegar código/locale, senão usa ID
                                    $lang = $model->language->code
                                        ?? $model->language->locale
                                        ?? $model->language_id;

                                    // monta URL curta no formato /p/<group>/<lang>/<slug>
                                    $url = Yii::$app->urlManager->createUrl([
                                        "/p/{$group}/{$lang}/{$model->slug}"
                                    ]);

                                    return Html::a(
                                        $url,
                                        'javascript:void(0);',
                                        [
                                            'class' => 'copy-url-link text-decoration-none',
                                            'data-url' => $url,
                                            'title' => Yii::t('app', 'Click to copy URL'),
                                        ]
                                    );
                                },
                            ],
                            [
                                'class' => croacworks\essentials\components\gridview\ActionColumnCustom::class,
                                'template' => '{view} {update} {delete} {public}',
                                'buttons' => [
                                    'public' => function ($url, $model, $key) {
                                        $group = (int)($model->group_id ?: 1);

                                        // tenta pegar código/locale, senão usa ID
                                        $lang = $model->language->code
                                            ?? $model->language->locale
                                            ?? $model->language_id;

                                        // monta URL curta no formato /p/<group>/<lang>/<slug>
                                        $url = Yii::$app->urlManager->createUrl([
                                            "/p/{$group}/{$lang}/{$model->slug}"
                                        ]);
                                        // ícone/estilo: adapte para seu tema (CoreUI/Bootstrap)
                                        return Html::a(
                                            '<i class="fas fa-link"></i>',
                                            $url,
                                            [
                                                'class' => 'btn btn-sm btn-outline-primary',
                                                'title' => Yii::t('app', 'Open public page'),
                                                'target' => '_blank',
                                                'data-pjax' => 0,
                                            ]
                                        );
                                    },
                                ],
                                'visibleButtons' => [
                                    // só mostra se a página estiver ativa
                                    'public' => fn($model) => (int)$model->status === 1,
                                ],
                            ]

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