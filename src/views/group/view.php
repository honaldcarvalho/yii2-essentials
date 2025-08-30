<?php

use yii\widgets\Pjax;
use yii\widgets\DetailView;
use croacworks\essentials\models\User;
use croacworks\essentials\widgets\AppendModel;
use yii\bootstrap5\Html;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\Group */

$this->title = Yii::t('app', 'View Group: {name}', [
    'name' => $model->name,
]);

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Groups'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

$buttons = [
    [
        'controller' => 'role',
        'action' => 'apply-templates',
        'icon' => '<i class="fas fa-plus-square mr-2"></i>',
        'text' => Yii::t('app', 'Add Roles'),
        'link' => "/role/apply-templates?group_id={$model->id}&reseed=1",
        'options' => [
            'class' => 'btn btn-outline-success btn-block-m',
            'data-ajax' => '1',
            'data-action' => 'apply',
            'data-method' => 'post',
            'data-pjax' => '0', // <- evita PJAX interceptar
        ],
    ],
    [
        'controller' => 'role',
        'action' => 'remove-roles',
        'icon' => '<i class="fas fa-minus-square mr-2"></i>',
        'text' => Yii::t('app', 'Remove Roles'),
        'link' => "/role/remove-roles?group_id={$model->id}&only_auto=0",
        'options' => [
            'class' => 'btn btn-outline-danger btn-block-m',
            'data-ajax' => '1',
            'data-action' => 'remove',
            'data-method' => 'post',
            'data-confirm' => Yii::t('app', 'This will remove all roles from this group. Proceed?'),
            'data-pjax' => '0', // <- evita PJAX interceptar
        ]
    ],
];

$js = <<<JS
(function(){
    function getCsrf(){
        if (window.yii && typeof yii.getCsrfToken === 'function') return yii.getCsrfToken();
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    async function postJson(url){
        var resp = await fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': getCsrf(),
                'Cache-Control': 'no-cache'
            }
        });
        try { return await resp.json(); } catch(e) { 
            return { success:false, message: await resp.text() }; 
        }
    }

    document.addEventListener('click', async function(evt){
        var anchor = evt.target.closest('a[data-ajax="1"]');
        if (!anchor) return;

        // impede navegação normal E impede que o PJAX intercepte
        evt.preventDefault();
        evt.stopPropagation();
        if (typeof evt.stopImmediatePropagation === 'function') {
            evt.stopImmediatePropagation();
        }

        var url = anchor.getAttribute('href') || anchor.dataset.url || '';
        var actionType = anchor.dataset.action || '';
        var confirmText = anchor.dataset.confirm || '';

        if (actionType === 'remove' && confirmText) {
            if (window.Swal) {
                var conf = await Swal.fire({
                    icon: 'warning',
                    title: 'Confirmação',
                    text: confirmText,
                    showCancelButton: true,
                    confirmButtonText: 'Sim',
                    cancelButtonText: 'Não'
                });
                if (!conf.isConfirmed) return;
            } else if (!confirm(confirmText)) {
                return;
            }
        }

        var result = await postJson(url);

        var ok = !!(result && result.success);
        var msg = (result && (result.message || result.error)) || (ok ? 'Operação concluída.' : 'Falha na operação.');

        if (window.Swal) {
            Swal.fire({ icon: ok ? 'success' : 'error', title: ok ? 'Pronto' : 'Ops', text: msg });
        } else {
            alert((ok ? 'OK: ' : 'ERRO: ') + msg);
        }

        if (ok) {
            if (window.jQuery && jQuery.pjax) {
                jQuery.pjax.reload({
                    container: '#pjax-roles',
                    timeout: 0,
                    scrollTo: false,
                    replace: false,
                    // força refetch da própria URL da página (evita cache estranho)
                    url: window.location.href
                });
            } else {
                location.reload();
            }
        }
    });
})();
JS;
$this->registerJs($js);

?>
<div class="user-update">
    <h1><?= Html::encode($this->title) ?></h1>
    <p>
        <?= croacworks\essentials\widgets\DefaultButtons::widget(['controller' => 'Group', 'model' => $model, 'extras' => $buttons])  ?>
    </p>
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'parente.name:text:' . Yii::t('app', 'Parent'),
            'level',
            'name',
            'status:boolean',
        ],
    ]) ?>

    <?= AppendModel::widget([
        'title' => Yii::t('app', 'Users'),
        'attactModel' => 'UserGroup',
        'uniqueId' => 'UserAppend',
        'controller' => 'configuration',
        'template' => '{edit}{remove}',
        'attactClass' => 'croacworks\\essentials\\models\\UserGroup',
        'dataProvider' => new \yii\data\ActiveDataProvider([
            'query' => $model->getUserGroups(),
        ]),
        'showFields' => [
            'user.profile.fullname',
            'user.email',
            [
                'attribute' => 'user.created_at',
                'format' => 'date',
                'label' => Yii::t('app', 'Created At'),
            ],
            [
                'attribute' => 'user.updated_at',
                'format' => 'date',
                'label' => Yii::t('app', 'Updated At'),
            ],
            'user.status:boolean',
        ],
        'fields' =>
        [
            [
                'name' => 'group_id',
                'type' => 'hidden',
                'value' => $model->id
            ],
            [
                'name' => 'user_id',
                'value' => yii\helpers\ArrayHelper::map(User::find()->select(['id', "concat(username,' - ',email) as name"])->asArray()->all(), 'id', 'name'),
                'type' => 'select2'
            ],

        ]
    ]); ?>


    <?php Pjax::begin([
        'id' => 'pjax-roles',
        'timeout' => 0,
        'enablePushState' => false,
        'clientOptions' => [
            'type' => 'GET',
            'scrollTo' => false,
        ],
    ]); ?>
    <?= AppendModel::widget([
        'new_button' => false,
        'title' => Yii::t('app', 'Roles'),
        'attactModel' => 'Role',
        'uniqueId' => 'rolesAppend',
        'controller' => 'roles',
        'template' => '',
        'attactClass' => 'croacworks\\essentials\\models\\Role',
        'dataProvider' => new \yii\data\ActiveDataProvider([
            'query' => $model->getRoles(),
        ]),
        'showFields' => [
            'id',
            'user.fullname:text:' . Yii::t('app', 'User'),
            'group.name:text:' . Yii::t('app', 'Role'),
            'controller',
            [
                'attribute' => 'actions',
                'value' => function ($data) {
                    return str_replace(';', ' | ', $data->actions);
                }
            ],
            'origin',
            'status:boolean'
        ]
    ]); ?>
    <?php Pjax::end(); ?>
</div>