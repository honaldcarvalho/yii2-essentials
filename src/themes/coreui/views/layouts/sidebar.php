<?php

use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\controllers\CommonController;
use croacworks\essentials\themes\coreui\widgets\CoreuiMenu;
use croacworks\essentials\models\Configuration;
use croacworks\essentials\models\Role;
use croacworks\essentials\models\SysMenu;

$config   = Configuration::get();
$assetDir = CommonController::getAssetsDir();

$nameSplit = explode(' ', Yii::$app->user->identity->username);
$nameUser  = $nameSplit[0] . (isset($nameSplit[1]) ? ' ' . end($nameSplit) : '');

/**
 * Regra de exibição baseada em permissões.
 * $visibleCsv: lista "index;view;create" | '*' | '' (usa $fallbackActionCsv) | null (usa $fallbackActionCsv)
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

/**
 * Item ativo:
 * 1) Prioriza sys_menus.active: compara com $controllerId e "$controllerId/$actionId".
 *    Aceita múltiplos separados por ';' (ex.: "user;user/update;post/index").
 * 2) Fallback: sys_menus.controller (FQCN) + sys_menus.action ('*' ou "index;view").
 */
function isActiveFor(SysMenu $item, string $controllerId, string $actionId, string $controllerFQCN): bool
{
    $activeExpr = trim((string)$item->active);
    if ($activeExpr !== '') {
        $targets = array_filter(array_map('trim', explode(';', $activeExpr)), 'strlen');
        foreach ($targets as $t) {
            if ($t === $controllerId || $t === $controllerId . '/' . $actionId) {
                return true;
            }
        }
        // Se explicitou "active" e não bateu, não cai no fallback.
        return false;
    }

    // Fallback FQCN + actions
    if (!$item->controller) return false;
    if ($item->controller !== $controllerFQCN) return false;

    $acts = trim((string)$item->action);
    if ($acts === '' || $acts === '*') return true;

    $allowed = array_filter(array_map('trim', explode(';', $acts)), 'strlen');
    return in_array($actionId, $allowed, true);
}

/** Monta recursivamente os nós do menu a partir de sys_menus */
function buildNodes(?int $parentId, string $controllerId, string $actionId, string $controllerFQCN): array
{
    $items = SysMenu::find()
        ->where(['parent_id' => $parentId, 'status' => true])
        ->orderBy(['order' => SORT_ASC])
        ->all();

    $nodes = [];

    foreach ($items as $item) {
        // Toggles duros
        if (!$item->show) continue;
        if ($item->only_admin && !AuthorizationController::isAdmin()) continue;

        $isGroup  = ($item->url === '#');
        $children = buildNodes($item->id, $controllerId, $actionId, $controllerFQCN);

        // Visibilidade
        if ($isGroup) {
            // Grupo fica visível se algum filho estiver visível
            $isVisible = false;
            foreach ($children as $c) {
                if (!empty($c['visible'])) { $isVisible = true; break; }
            }
        } else {
            $isVisible = allowedByVisible($item->controller, $item->visible, $item->action);
        }

        // Active (somente item simples)
        $active = false;
        if (!$isGroup) {
            $active = isActiveFor($item, $controllerId, $actionId, $controllerFQCN);
        }

        // Nó do menu
        $node = [
            'label'     => Yii::t('app', (string)$item->label),
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

        if ($isVisible || ($isGroup && !empty($children))) {
            $nodes[] = $node;
        }
    }

    return $nodes;
}

// Contexto atual
$currentControllerId  = Yii::$app->controller->id;
$currentActionId      = Yii::$app->controller->action->id;
$currentControllerFQCN = get_class(Yii::$app->controller);

// Monta nós
$nodes = buildNodes(null, $currentControllerId, $currentActionId, $currentControllerFQCN);

// Acrescenta Logout
$nodes[] = [
    'label' => Yii::t('app', 'Logout'),
    'icon'  => 'cil-account-logout',
    'url'   => ['/site/logout'],
];

// (Opcional) divisor
$nodes[] = ['divider' => true];
?>

<div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar">
    <div class="sidebar-header border-bottom">
        <div class="sidebar-brand">
            <?php
            if (!empty($config->file_id) && $config->file !== null) {
                $url = Yii::getAlias('@web') . $config->file->urlThumb;
                echo '<img class="sidebar-brand-full" src="'.htmlspecialchars($url).'" alt="'.htmlspecialchars($config->title).'" height="32">';
                echo '<img class="sidebar-brand-narrow" src="'.htmlspecialchars($url).'" alt="'.htmlspecialchars($config->title).'" height="32">';
            } else {
                echo '<img class="sidebar-brand-full" src="'.$assetDir.'/images/croacworks-logo-hq.png" alt="'.htmlspecialchars($config->title).'" height="32">';
                echo '<img class="sidebar-brand-narrow" src="'.$assetDir.'/images/croacworks-logo-hq.png" alt="'.htmlspecialchars($config->title).'" height="32">';
            }
            ?>
            <?= htmlspecialchars($config->title) ?>
        </div>
        <button class="btn-close d-lg-none" type="button" data-coreui-theme="dark" aria-label="Close"
            onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()"></button>
    </div>

    <div class="px-3 py-3 border-bottom d-flex align-items-center gap-2">
        <div class="flex-shrink-0">
            <?php if (Yii::$app->user->identity->profile && Yii::$app->user->identity->profile->file): ?>
                <img src="<?= Yii::$app->user->identity->profile->file->url; ?>" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">
            <?php else: ?>
                <svg width="32" height="32">
                    <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-user"></use>
                </svg>
            <?php endif; ?>
        </div>
        <div class="flex-grow-1">
            <div class="fw-semibold text-white-50"><?= htmlspecialchars($nameUser) ?></div>
            <div class="small text-white-50"><?= htmlspecialchars($config->title) ?></div>
        </div>
    </div>

    <?= CoreuiMenu::widget([
        'items'            => $nodes,
        'coreuiIconBaseHref' => $assetDir . '/vendors/@coreui/icons/svg/free.svg',
        'compactChildren'  => true,
        'openOnActive'     => true,
        'activeLinkClass'  => 'active',
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
