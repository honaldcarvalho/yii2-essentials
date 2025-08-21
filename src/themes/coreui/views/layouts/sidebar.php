<?php

use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\controllers\CommonController;
use croacworks\essentials\themes\coreui\widgets\CoreuiMenu;
use croacworks\essentials\models\Configuration;
use croacworks\essentials\models\Role;
use croacworks\essentials\models\SysMenu;

// Pré-existentes do seu código:
$params     = Configuration::get();
$assetDir   = CommonController::getAssetsDir(); // ajuste se seu tema definir outro helper
$name_split = explode(' ', Yii::$app->user->identity->username);
$name_user  = $name_split[0] . (isset($name_split[1]) ? ' ' . end($name_split) : '');

// ... allowedByVisible() e getNodes() iguais aos seus ...
/**
 * Regra de exibição baseada em permissões:
 * - $visibleCsv: lista de actions separadas por ';' (ex.: "index;view;create").
 * - Se $visibleCsv = '*' → aparece se existir QUALQUER role ativa para esse controller.
 * - Se $visibleCsv vazio → usa $fallbackActionCsv; se também vazio → comporta como '*'.
 */
function allowedByVisible(?string $controllerFQCN, ?string $visibleCsv, ?string $fallbackActionCsv = null): bool
{
    if (AuthorizationController::isGuest()) return false;
    if (AuthorizationController::isAdmin()) return true;

    $controllerFQCN = trim((string)$controllerFQCN);
    if ($controllerFQCN === '') return false;

    $csv = trim((string)$visibleCsv);
    if ($csv === '') {
        $csv = trim((string)$fallbackActionCsv);
        if ($csv === '') $csv = '*';
    }

    if ($csv === '*') {
        $groups = AuthorizationController::getUserGroups() ?? [];
        return Role::find()
            ->where(['controller' => $controllerFQCN, 'status' => 1])
            ->andWhere(['in', 'group_id', $groups])
            ->exists();
    }

    foreach (array_filter(array_map('trim', explode(';', $csv)), 'strlen') as $act) {
        if (AuthorizationController::verAuthorization($controllerFQCN, $act)) {
            return true;
        }
    }
    return false;
}

/** Monta recursivamente os nós do menu a partir de sys_menus */
function getNodes($parentId = null): array
{

    $items = SysMenu::find()
        ->where(['parent_id' => $parentId, 'status' => true])
        ->orderBy(['order' => SORT_ASC])
        ->all();

    $nodes = [];
    $currentFQCN   = get_class(Yii::$app->controller);
    $currentAction = Yii::$app->controller->action->id;

    foreach ($items as $item) {
        // Hard toggles
        if (!$item->show) continue;
        if ($item->only_admin && !AuthorizationController::isAdmin()) continue;

        $isGroup  = ($item->url === '#');
        $children = getNodes($item->id);

        // Visibilidade
        if ($isGroup) {
            // Grupo aparece se tiver ao menos um filho visível
            $isVisible = false;
            foreach ($children as $c) {
                if (!empty($c['visible'])) { $isVisible = true; break; }
            }
        } else {
            $isVisible = allowedByVisible($item->controller, $item->visible, $item->action);
        }

        // Active (apenas itens simples com controller)
        $active = false;
        if (!$isGroup && $item->controller) {
            $actions = trim((string)$item->action);
            if ($item->controller === $currentFQCN) {
                if ($actions === '' || $actions === '*') {
                    $active = true;
                } else {
                    $allowed = array_map('trim', explode(';', $actions));
                    $active  = in_array($currentAction, $allowed, true);
                }
            }
        }

        // Nó
        $node = [
            'label'     => Yii::t('app', $item->label),
            'icon'      => (string)$item->icon,
            'iconStyle' => (string)$item->icon_style,
            'url'       => [$item->url ?: '#'],
            'visible'   => $isVisible,
        ];

        if ($isGroup) {
            $node['items'] = $children;
        } else {
            $node['active'] = $active;
        }

        // Inclui se visível ou (grupo com filhos)
        if ($isVisible || ($isGroup && !empty($children))) {
            $nodes[] = $node;
        }
    }

    return $nodes;
}

$nodes = getNodes(null);

// Exemplo: acrescentar Logout ao fim
$nodes[] = [
    'label' => Yii::t('app', 'Logout'),
    'icon'  => 'cil-account-logout',
    'url'   => ['/site/logout'],
];

// (Opcional) header e divider de exemplo
// array_unshift($nodes, ['label' => 'Theme', 'header' => true]);
$nodes[] = ['divider' => true];
?>

<div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar">
    <div class="sidebar-header border-bottom">
        <div class="sidebar-brand">
            <?php
            // Marca + variação estreita (se tiver seus próprios svgs)
            // Caso prefira a logo da instância:
            if (!empty($params->file_id) && $params->file !== null) {
                $url = Yii::getAlias('@web') . $params->file->urlThumb;
                echo '<img class="sidebar-brand-full" src="'.htmlspecialchars($url).'" alt="'.htmlspecialchars($params->title).'" height="32">';
                echo '<img class="sidebar-brand-narrow" src="'.htmlspecialchars($url).'" alt="'.htmlspecialchars($params->title).'" height="32">';
            } else {
                echo '<img class="sidebar-brand-full" src="'.$assetDir.'/images/croacworks-logo-hq.png" alt="'.htmlspecialchars($params->title).'" height="32">';
                echo '<img class="sidebar-brand-narrow" src="'.$assetDir.'/images/croacworks-logo-hq.png" alt="'.htmlspecialchars($params->title).'" height="32">';
            }
            ?>
        </div>
        <button class="btn-close d-lg-none" type="button" data-coreui-theme="dark" aria-label="Close"
            onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()"></button>
    </div>

    <!-- (Opcional) bloco de usuário -->
    <div class="px-3 py-3 border-bottom d-flex align-items-center gap-2">
        <div class="flex-shrink-0">
            <?php if (Yii::$app->user->identity->file): ?>
                <img src="<?= Yii::$app->user->identity->file->url; ?>" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">
            <?php else: ?>
                <svg width="32" height="32">
                    <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-user"></use>
                </svg>
            <?php endif; ?>
        </div>
        <div class="flex-grow-1">
            <div class="fw-semibold text-white-50"><?= htmlspecialchars($name_user) ?></div>
            <div class="small text-white-50"><?= htmlspecialchars($params->title) ?></div>
        </div>
    </div>

    <!-- Navegação -->
    <?= CoreuiMenu::widget([
        'items' => $nodes,
        // Se seus SVGs estiverem em outro caminho, ajuste aqui:
        'coreuiIconBaseHref' => $assetDir . '/vendors/@coreui/icons/svg/free.svg',
        'compactChildren' => true,
        'openOnActive'    => true,
        'activeLinkClass' => 'active',
        // Se quiser sobrescrever classes do <ul> raiz:
        'options' => [
            'class' => 'sidebar-nav',
            'data-coreui' => 'navigation',
            'data-simplebar' => '',
        ],
    ]); ?>

    <div class="sidebar-footer border-top d-none d-md-flex">
        <button class="sidebar-toggler" type="button" data-coreui-toggle="unfoldable"></button>
    </div>
</div>
