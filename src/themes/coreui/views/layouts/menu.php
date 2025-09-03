<?php

use croacworks\essentials\controllers\CommonController;
use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\models\Configuration;
use croacworks\essentials\models\SysMenu;
use croacworks\essentials\themes\coreui\widgets\Menu;

if (Yii::$app->user->isGuest) {
    return false;
}

$config = Configuration::get();
$assetsDir = CommonController::getAssetsDir();

if (!Yii::$app->user->isGuest) {
    $name_split = explode(' ', Yii::$app->user->identity->profile->fullname);
    $name_user = $name_split[0] . (isset($name_split[1]) ? ' ' . end($name_split) : '');
    $controller_id = Yii::$app->controller->id;
    $group = Yii::$app->session->get('group');
}

if (!empty($config->file_id) && $config->file != null) {
    $url = Yii::getAlias('@web') . $config->file->urlThumb;
    $login_image = "<img alt='" . htmlspecialchars($config->title, ENT_QUOTES) . "' class='sidebar-brand-full' src='{$url}' style='height:32px' />";
    $login_image_min = "<img alt='" . htmlspecialchars($config->title, ENT_QUOTES) . "' class='sidebar-brand-narrow' src='{$url}' style='height:32px' />";
} else {
    $logo = "{$assetsDir}/img/croacworks-logo-hq.png";
    $login_image = "<img alt='" . htmlspecialchars($config->title, ENT_QUOTES) . "' class='sidebar-brand-full' src='{$logo}' style='height:32px' />";
    $login_image_min = "<img alt='" . htmlspecialchars($config->title, ENT_QUOTES) . "' class='sidebar-brand-narrow' src='{$logo}' style='height:32px' />";
}

/** Montagem dos nós (mesma lógica original) */
function getNodes($controller_id, $id = null)
{
    $items = SysMenu::find()->where(['parent_id' => $id, 'status' => true])->orderBy(['order' => SORT_ASC])->all();
    $nodes = [];

    foreach ($items as $item) {
        if ($item['url'] == '#' || ($item['url'] != '#' && $item['parent_id'] == null)) {

            $visible_parts = $item['visible'] ? explode(';', $item['visible']) : [];
            $isVisible = true;
            $item_nodes = getNodes($controller_id, $item['id']);

            if (empty($item_nodes) && $item['url'] == '#') {
                $isVisible = false;
            } else {

                if (count($visible_parts) > 1) {
                    $isVisible =  AuthorizationController::isMaster() || AuthorizationController::verAuthorization($visible_parts[0],$visible_parts[1],null,$item['path']);
                } else if (count($visible_parts) === 1) {
                    // verifica se algum filho é visível; se sim, mostra o grupo
                    $isVisible = false;
                    foreach ($item_nodes as $item_node) {
                        if (!empty($item_node['visible'])) {
                            $isVisible = true;
                            break;
                        }
                    }
                } else {
                    $isVisible = false;
                }
            }

            $node = [
                'label'     => Yii::t('app', $item['label']),
                'icon'      => "{$item['icon']}",
                'iconStyle' => "{$item['icon_style']}",
                'url'       => ["{$item['url']}"],
                'visible'   => $isVisible,
                'items'     => $item_nodes,
            ];

            if ($item['url'] != '#') {
                $node['active'] =
                    ($controller_id == "{$item['active']}") ||
                    ($controller_id . "/" . Yii::$app->controller->action->id == "{$item['active']}");
            }

            if(!$item['only_admin'] || $item['only_admin'] &&  AuthorizationController::isMaster()) {
                $nodes[] = $node;
            }
        } else {
            $visible_parts = $item['visible'] ? explode(';', $item['visible']) : [];
            $isVisible = true;

            if (count($visible_parts) > 1) {
                $isVisible =  AuthorizationController::isMaster() || AuthorizationController::verAuthorization($visible_parts[0],$visible_parts[1],null,$item['path']);
            } else if (empty($visible_parts)) {
                $isVisible = false;
            }

            if(!$item['only_admin'] || $item['only_admin'] &&  AuthorizationController::isMaster()) {
                $nodes[] = [
                    'label'     => Yii::t('app', $item['label']),
                    'icon'      => "{$item['icon']}",
                    'iconStyle' => "{$item['icon_style']}",
                    'url'       => ["{$item['url']}"],
                    'visible'   => $isVisible,
                    'active'    => ($controller_id == "{$item['active']}") ||
                                   ($controller_id . "/" . Yii::$app->controller->action->id == "{$item['active']}")
                ];
            }
        }
    }

    return $nodes;
}

$nodes = getNodes(Yii::$app->controller->id);
?>
<!-- Sidebar CoreUI -->
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
    <!-- Menu (gera UL .sidebar-nav internamente) -->
    <?= Menu::widget([
        "options" => [
            'class' => 'sidebar-nav', // CoreUI
            'role'  => 'menu',
        ],
        'items' => array_merge(
            $nodes,
            [
                [
                    'label' => 'Logout',
                    'icon'  => 'fas fa-sign-out-alt',
                    'iconStyle' => 'fas',
                    'url'   => ['/site/logout']
                ]
            ]
        )
    ]); ?>


    <div class="sidebar-footer border-top d-none d-md-flex">
        <button class="sidebar-toggler" type="button" data-coreui-toggle="unfoldable"></button>
    </div>
</div>
