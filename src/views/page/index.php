<?php

use croacworks\essentials\models\Configuration;
use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\grid\GridView;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\Page $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Pages';
$this->params['breadcrumbs'][] = $this->title;
$js = <<< JS
    document.addEventListener('click', function (e) {
        const target = e.target.closest('.copy-url-link');
        if (!target) return;

        const url = target.getAttribute('data-url');
        if (!url) return;

        navigator.clipboard.writeText(url).then(() => {
            toastr.success('URL copiada!', '', {timeOut: 2000});
        }).catch(err => {
            toastr.error('Erro ao copiar URL', '', {timeOut: 3000});
            console.error(err);
        });

        e.preventDefault();
    });
JS;
$this->registerJs($js, \yii\web\View::POS_READY);
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
                            'language.code:text:' . Yii::t('app', 'Language'),
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
                                'value' => function ($m) {
                                    $home = rtrim(Configuration::get()->homepage, '/');
                                    $g = (int)($m->group_id ?: 1);
                                    $l = $m->language->code ?? $m->language->locale ?? $m->language_id ?? '';
                                    $sec = $m->pageSection->slug ?? '';  // ajuste o nome da relação se for diferente
                                    $slug = $m->slug;

                                    $path = "/p/{$g}" . ($sec ? "/" . rawurlencode($sec) : '') . ($l ? "/" . rawurlencode($l) : '') . "/" . rawurlencode($slug);
                                    $url  = $home . $path;

                                    return Html::a($url, 'javascript:void(0);', [
                                        'class' => 'copy-url-link text-decoration-none',
                                        'data-url' => $url,
                                        'title' => Yii::t('app', 'Click to copy URL'),
                                    ]);
                                },
                            ],
                            [
                                'class' => croacworks\essentials\components\gridview\ActionColumnCustom::class,
                                'template' => '{view} {update} {delete} {public}',
                                'buttons' => [
                                    'public' => function ($url, $m) {
                                        $home = rtrim(Configuration::get()->homepage, '/');
                                        $g = (int)($m->group_id ?: 1);
                                        $l = $m->language->code ?? $m->language->locale ?? $m->language_id ?? '';
                                        $sec = $m->pageSection->slug ?? '';
                                        $slug = $m->slug;

                                        $path = "/p/{$g}" . ($sec ? "/" . rawurlencode($sec) : '') . ($l ? "/" . rawurlencode($l) : '') . "/" . rawurlencode($slug);
                                        $publicUrl = $home . $path;

                                        return Html::a('<i class="fas fa-link"></i>', $publicUrl, [
                                            'class' => 'btn btn-sm btn-outline-primary',
                                            'title' => Yii::t('app', 'Open public page'),
                                            'target' => '_blank',
                                            'data-pjax' => 0,
                                        ]);
                                    },
                                ],
                                'visibleButtons' => ['public' => fn($m) => (int)$m->status === 1],
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