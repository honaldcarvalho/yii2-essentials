<?php

use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\controllers\CommonController;
use croacworks\essentials\themes\coreui\widgets\CoreuiMenu;
use croacworks\essentials\models\Configuration;
use croacworks\essentials\models\Role;
use croacworks\essentials\models\SysMenu;

$config     = Configuration::get();
$assetDir   = CommonController::getAssetsDir();
$user = Yii::$app->user->identity;
$name_split = explode(' ', $user->username);
$name_user  = $name_split[0] . (isset($name_split[1]) ? ' ' . end($name_split) : '');

/**
 * Visibilidade por permissões.
 */
function allowedByVisible(?string $controllerFQCN, ?string $visibleCsv, ?string $fallbackActionCsv = null): bool
{
    if (AuthorizationController::isGuest()) return false;
    if (AuthorizationController::isMaster()) return true;

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
 * Monta recursivamente os nós.
 */
function getNodes($parentId = null): array
{
    $items = SysMenu::find()
        ->where(['parent_id' => $parentId, 'status' => true])
        ->orderBy(['order' => SORT_ASC])
        ->all();

    $nodes = [];

    foreach ($items as $item) {
        if (!$item->show) continue;
        if ($item->only_admin && !AuthorizationController::isMaster()) continue;

        $isGroup  = ($item->url === '#');
        $children = $isGroup ? getNodes($item->id) : [];

        // Visibilidade
        if ($isGroup) {
            $isVisible = false;
            foreach ($children as $c) {
                if (!array_key_exists('visible', $c) || !empty($c['visible'])) {
                    $isVisible = true; break;
                }
            }
        } else {
            $isVisible = allowedByVisible($item->controller, $item->visible, $item->action);
        }

        // Nó base
        $node = [
            'label'     => Yii::t('app', $item->label),
            'icon'      => (string)$item->icon,
            'iconStyle' => (string)$item->icon_style,
            'url'       => [$item->url ?: '#'],
            'visible'   => $isVisible,
        ];

        if ($isGroup) {
            $node['items'] = $children;               // grupo não recebe 'active'
        } else {
            // informe sempre o FQCN para desambiguar controller
            $node['controller'] = $item->controller ?: null;
            $node['active'] = $item->active;        // ex.: "index;view;update"
        }

        if ($isVisible || ($isGroup && !empty($children))) {
            $nodes[] = $node;
        }
    }

    return $nodes;
}

$nodes = getNodes(null);

// Logout opcional
$nodes[] = [
    'label' => Yii::t('app', 'Logout'),
    'icon'  => 'cil-account-logout',
    'active' => 'logout',
    'url'   => ['/site/logout'],
];

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
            <?= $config->title ?>
        </div>
        <button class="btn-close d-lg-none" type="button" data-coreui-theme="dark" aria-label="Close"
            onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()"></button>
    </div>

    <!-- Bloco de usuário (opcional) -->
    <div class="px-3 py-3 border-bottom d-flex align-items-center gap-2">
        <a href="/user/{$user->id}" class="flex-shrink-0">
            <?php if ($user->profile && $user->profile->file): ?>
                <img src="<?= $user->profile->file->url; ?>" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">
            <?php else: ?>
                <svg width="32" height="32">
                    <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-user"></use>
                </svg>
            <?php endif; ?>
        </a>
        <div class="flex-grow-1">
            <a href="/user/{$user->id}" class="fw-semibold text-white-50"><?= htmlspecialchars($name_user) ?></a>
            <a href="/user/{$user->id}" class="small text-white-50"><?= htmlspecialchars($user->group->name) ?></a>
        </div>
    </div>

    <!-- Navegação -->
    <?= CoreuiMenu::widget([
        'items'              => $nodes,
        'coreuiIconBaseHref' => $assetDir . '/vendors/@coreui/icons/svg/free.svg',
        'compactChildren'    => true,
        'openOnActive'       => true,
        'activeLinkClass'    => 'active',
        'options'            => [
            'class' => 'sidebar-nav',
            'data-coreui' => 'navigation',
            'data-simplebar' => '',
        ],
    ]); ?>

    <div class="sidebar-footer border-top d-none d-md-flex">
        <button class="sidebar-toggler" type="button" data-coreui-toggle="unfoldable"></button>
    </div>
</div>