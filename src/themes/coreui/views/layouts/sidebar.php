<?php

use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\controllers\CommonController;
use croacworks\essentials\themes\coreui\widgets\CoreuiMenu;
use croacworks\essentials\models\Configuration;
use croacworks\essentials\models\Role;
use croacworks\essentials\models\SysMenu;

// Pré-existentes do seu código:
$config     = Configuration::get();
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

function isNodeActive(SysMenu $item, string $currentFQCN, string $currentControllerId, string $currentActionId): bool
{
    // 1) Especificação vinda do campo "action"
    $spec = trim((string)$item->action);

    // 1.1) action vazio ou '*' → ativa para qualquer action do controller FQCN
    if ($spec === '' || $spec === '*') {
        return $item->controller && $item->controller === $currentFQCN;
    }

    // 1.2) Padrão "controller;action" → ex.: "menu;index"
    if (strpos($spec, ';') !== false && strpos($spec, '/') === false) {
        [$ctrlId, $actId] = array_map('trim', explode(';', $spec, 2));
        if ($ctrlId === '' && $actId === '') return false;
        if ($ctrlId !== '' && $ctrlId !== $currentControllerId) return false;
        if ($actId === '' || $actId === '*') return true;
        return $actId === $currentActionId;
    }

    // 1.3) Padrão só "controller" → ex.: "menu" (qualquer action de /menu/*)
    if (strpos($spec, '/') === false && strpos($spec, ';') === false) {
        return $spec === $currentControllerId;
    }

    // 1.4) Padrão "controller/action" (ou lista separada por vírgula)
    $routeNow = $currentControllerId . '/' . $currentActionId;
    foreach (array_map('trim', explode(',', $spec)) as $routeSpec) {
        if ($routeSpec === $routeNow) return true;
    }

    // 2) Fallback antigo: se controller FQCN bater e o "action" for lista de actions
    //    (ex.: "index;view;create"), ativa quando a action atual constar.
    if ($item->controller && $item->controller === $currentFQCN) {
        foreach (array_filter(array_map('trim', explode(';', $spec)), 'strlen') as $act) {
            if ($act === $currentActionId || $act === '*') return true;
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
    $currentFQCN         = get_class(Yii::$app->controller);
    $currentControllerId = Yii::$app->controller->id;
    $currentActionId     = Yii::$app->controller->action->id;

    foreach ($items as $item) {
        // Hard toggles
        if (!$item->show) continue;
        if ($item->only_admin && !AuthorizationController::isAdmin()) continue;

        $isGroup  = ($item->url === '#');
        $children = getNodes($item->id);

        // Visibilidade
        if ($isGroup) {
            $isVisible = false;
            foreach ($children as $c) {
                if (!empty($c['visible'])) { $isVisible = true; break; }
            }
        } else {
            $isVisible = allowedByVisible($item->controller, $item->visible, $item->action);
        }

        // Active
        $selfActive  = (!$isGroup) ? isNodeActive($item, $currentFQCN, $currentControllerId, $currentActionId) : false;
        $childActive = false;
        if ($isGroup) {
            foreach ($children as $c) {
                if (!empty($c['active'])) { $childActive = true; break; }
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
            $node['items']  = $children;
            $node['active'] = $childActive; // faz o grupo expandir
        } else {
            $node['active'] = $selfActive;
        }

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

    <!-- (Opcional) bloco de usuário -->
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
            <div class="fw-semibold text-white-50"><?= htmlspecialchars($name_user) ?></div>
            <div class="small text-white-50"><?= htmlspecialchars($config->title) ?></div>
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
