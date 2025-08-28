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

/** Monta recursivamente os nós do menu a partir de sys_menus */
function getNodes($controller_id, $id = null)
{
    $items = Menu::find()->where(['menu_id' => $id, 'status' => true])
        ->orderBy(['order' => SORT_ASC])->all();

    $nodes = [];
    foreach ($items as $item) {

        $visible_parts = explode(';', $item['visible']);
        $isVisible = true;

        // filhos primeiro (para sabermos se algum filho está ativo)
        $item_nodes = [];
        if ($item['url'] == '#' || ($item['url'] != '#' && $item['menu_id'] == null)) {
            $item_nodes = getNodes($controller_id, $item['id']);

            if (empty($item_nodes) && $item['url'] == '#') {
                $isVisible = false;
            } else {
                if (count($visible_parts) > 1) {
                    $isVisible = AuthController::verAuthorization($visible_parts[0], $visible_parts[1], null, $item['path']);
                } elseif (count($visible_parts) === 1) {
                    // mostra o pai se pelo menos um filho for visível
                    $isVisible = false;
                    foreach ($item_nodes as $n) {
                        if (!empty($n['visible'])) {
                            $isVisible = true;
                            break;
                        }
                    }
                } else {
                    $isVisible = false;
                }
            }
        } else {
            if (count($visible_parts) > 1) {
                $isVisible = AuthController::verAuthorization($visible_parts[0], $visible_parts[1], null, $item['path']);
            } elseif (empty($visible_parts)) {
                $isVisible = false;
            }
        }

        // --- CÁLCULO DO ACTIVE (corrigido) -----------------------------
        // Normaliza "menu;index" -> "menu/index"
        $activeRaw  = (string)$item['active'];
        $activeNorm = str_replace(';', '/', $activeRaw);

        $currentCtrlAction = $controller_id . '/' . Yii::$app->controller->action->id;

        // Regra:
        // - Se active = "menu"  -> ativa para qualquer action de /menu/*
        // - Se active = "menu;index" (ou "menu/index") -> ativa só em /menu/index
        $selfActive =
            ($controller_id === $activeRaw) ||
            ($controller_id === $activeNorm) ||
            ($currentCtrlAction === $activeRaw) ||
            ($currentCtrlAction === $activeNorm);

        // Pai deve ficar ativo/expandido se QUALQUER filho estiver ativo
        $childActive = false;
        foreach ($item_nodes as $n) {
            if (!empty($n['active'])) {
                $childActive = true;
                break;
            }
        }

        // Para itens com URL "#", usar childActive; para os demais, usar selfActive
        $isActive = ($item['url'] == '#') ? $childActive : $selfActive;
        // --- FIM ACTIVE -----------------------------------------------

        // Monta o node
        $node = [
            'label'   => Yii::t('app', $item['label']),
            'icon'    => "{$item['icon']}",
            'iconStyle' => "{$item['icon_style']}",
            'url'     => ["{$item['url']}"],
            'visible' => $isVisible,
            'items'   => $item_nodes,
            'active'  => $isActive,
        ];

        if (!$item['only_admin'] || ($item['only_admin'] && AuthController::isAdmin())) {
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
    'options' => [
        'class' => 'nav nav-pills nav-sidebar flex-column nav-child-indent',
        'data-widget' => 'treeview',
        'role' => 'menu',
        'data-accordion' => 'false',
    ],
    'activateItems' => true,        // garante que itens possam ficar ativos
    'activateParents' => true,      // EXPANDE o pai quando o filho está ativo
    'items' => array_merge($nodes, [
        ['label' => 'Logout', 'icon' => 'fas fa-sign-out-alt', 'url' => ['/site/logout']],
    ]),
]);?>

    <div class="sidebar-footer border-top d-none d-md-flex">
        <button class="sidebar-toggler" type="button" data-coreui-toggle="unfoldable"></button>
    </div>
</div>
