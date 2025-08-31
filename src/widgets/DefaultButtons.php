<?php

namespace croacworks\essentials\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use croacworks\essentials\controllers\AuthorizationController;

class DefaultButtons extends Widget
{
    /** @var string|null FQCN do controller. Se nulo, usa o atual. */
    public $controller = null;

    /** @var \yii\db\ActiveRecord|null */
    public $model = null;

    /** @var string HTML final dos botões */
    public $buttons = '';

    /** @var array[] extras: [
     *   'controller' => FQCN (opcional, se ausente usa controller atual),
     *   'action'     => 'minha-action',
     *   'link'       => ['rota', 'params' => ...],
     *   'icon'       => '<i class="..."></i>&nbsp;',
     *   'text'       => 'Rótulo',
     *   'options'    => ['class' => 'btn ...'],
     *   'visible'    => bool|callable
     * ]
     */
    public $extras = [];

    /** @var bool Se true, oculta botões se o registro não pertencer ao(s) grupo(s) do usuário.
     * DICA: o AuthorizationController::verAuthorization() já cuida disso via $model->verGroup.
     * Você pode deixar true para “curto-circuitar” o render antes.
     */
    public $verGroup = true;

    /** @var bool Se false, oculta tudo. */
    public $visible = true;

    /** @var array Mapa dos rótulos traduzíveis */
    public $buttons_name = [
        'index'  => 'List',
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'clone'  => 'Clone',
    ];

    /** @var string[] Quais botões exibir */
    public $show = ['index', 'create', 'update', 'delete'];

    /** @var string FQCN do controller atual (resolvido no init) */
    protected $currentControllerFQCN;

    public function init(): void
    {
        parent::init();

        // Resolve o FQCN do controller a usar nas checagens
        $this->currentControllerFQCN = $this->controller ?: get_class(Yii::$app->controller);

        if (!$this->visible) {
            $this->buttons = '';
            return;
        }

        // Se desejar um pré-cheque de grupo no registro (além do feito no AuthorizationController)
        $renderForRecord = true;
        if ($this->verGroup && $this->model && $this->model->hasAttribute('group_id')) {
            $userGroups = AuthorizationController::getUserGroups() ?? [];
            if (
                $this->model->group_id !== null &&
                !in_array((int)$this->model->group_id, array_map('intval', $userGroups), true) &&
                !(AuthorizationController::isAdmin())
            ) {
                $renderForRecord = false;
            }
        }

        $this->buttons .= Html::beginTag('div', ['class' => 'btn-group']);

        // INDEX
        if (
            in_array('index', $this->show, true) &&
            $renderForRecord &&
            (AuthorizationController::isAdmin() ||
             AuthorizationController::verAuthorization($this->currentControllerFQCN, 'index', null))
        ) {
            $this->buttons .= Html::a(
                '<i class="fas fa-list-ol"></i>&nbsp;<span class="btn-text">' . Yii::t('app', $this->buttons_name['index']) . '</span>',
                ['index'],
                ['class' => 'btn btn-primary']
            );
        }

        // CREATE
        if (
            in_array('create', $this->show, true) &&
            $renderForRecord &&
            (AuthorizationController::isAdmin() ||
             AuthorizationController::verAuthorization($this->currentControllerFQCN, 'create', null))
        ) {
            $this->buttons .= Html::a(
                '<i class="fas fa-plus-square"></i>&nbsp;<span class="btn-text">' . Yii::t('app', $this->buttons_name['create']) . '</span>',
                ['create'],
                ['class' => 'btn btn-success']
            );
        }

        // UPDATE
        if (
            in_array('update', $this->show, true) &&
            $renderForRecord && $this->model &&
            (AuthorizationController::isAdmin() ||
             AuthorizationController::verAuthorization($this->currentControllerFQCN, 'update', $this->model))
        ) {
            $pk = $this->model->getPrimaryKey(true); // array PK (composta ou simples)
            $link = array_merge(['update'], $pk);
            $this->buttons .= Html::a(
                '<i class="fas fa-edit"></i>&nbsp;<span class="btn-text">' . Yii::t('app', $this->buttons_name['update']) . '</span>',
                $link,
                ['class' => 'btn btn-warning']
            );
        }

        // DELETE
        if (
            in_array('delete', $this->show, true) &&
            $renderForRecord && $this->model &&
            (AuthorizationController::isAdmin() ||
             AuthorizationController::verAuthorization($this->currentControllerFQCN, 'delete', $this->model))
        ) {
            $pk = $this->model->getPrimaryKey(true);
            $link = array_merge(['delete'], $pk);
            $this->buttons .= Html::a(
                '<i class="fas fa-trash"></i>&nbsp;<span class="btn-text">' . Yii::t('app', $this->buttons_name['delete']) . '</span>',
                $link,
                [
                    'class' => 'btn btn-danger input-group-text',
                    'data'  => [
                        'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                        'method'  => 'post',
                    ],
                ]
            );
        }

        // CLONE
        if (
            in_array('clone', $this->show, true) &&
            $renderForRecord && $this->model &&
            (AuthorizationController::isAdmin() ||
             AuthorizationController::verAuthorization($this->currentControllerFQCN, 'clone', $this->model))
        ) {
            $pk = $this->model->getPrimaryKey(true);
            $link = array_merge(['clone'], $pk);
            $this->buttons .= Html::a(
                '<i class="fas fa-clone"></i>&nbsp;<span class="btn-text">' . Yii::t('app', $this->buttons_name['clone']) . '</span>',
                $link,
                ['class' => 'btn btn-dark']
            );
        }

        // EXTRAS
        foreach ($this->extras as $extra) {
            // visibilidade custom (bool ou callable)
            $visible = $extra['visible'] ?? true;
            if (is_callable($visible)) {
                try { $visible = (bool)call_user_func($visible, $this->model); } catch (\Throwable $e) { $visible = false; }
            }
            if (!$visible) continue;

            $extraController = $extra['controller'] ?? $this->currentControllerFQCN;
            $extraAction     = $extra['action']     ?? null;
            $extraLink       = $extra['link']       ?? ['#'];
            $extraIcon       = $extra['icon']       ?? '';
            $extraTextKey    = $extra['text']       ?? '';
            $extraOptions    = $extra['options']    ?? ['class' => 'btn btn-secondary'];

            if (!$extraAction) continue;

            if (AuthorizationController::isAdmin() ||
                AuthorizationController::verAuthorization($extraController, $extraAction, $this->model)) {
                $this->buttons .= Html::a(
                    $extraIcon . '<span class="btn-text">' . Yii::t('app', $extraTextKey) . '</span>',
                    $extraLink,
                    $extraOptions
                );
            }
        }

        $this->buttons .= Html::endTag('div');
    }

    public function run()
    {
        return $this->buttons;
    }
}
