<?php

use croacworks\essentials\controllers\CommonController;
use croacworks\essentials\models\Configuration;
use croacworks\essentials\widgets\CoreUiMenu;
// Pré-existentes do seu código:
$config     = Configuration::get();
$assetDir   = CommonController::getAssetsDir(); // ajuste se seu tema definir outro helper
$name_split = explode(' ', Yii::$app->user->identity->username);
$name_user  = $name_split[0] . (isset($name_split[1]) ? ' ' . end($name_split) : '');

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

<?php

echo CoreUiMenu::widget([
     'items' => [
         [
             'label' => 'Starter Pages',
             'icon' => 'tachometer-alt',
             'badge' => '<span class="right badge badge-info">2</span>',
             'items' => [
                 ['label' => 'Active Page', 'url' => ['site/index'], 'iconStyle' => 'far'],
                 ['label' => 'Inactive Page', 'iconStyle' => 'far'],
             ]
         ],
         ['label' => 'Simple Link', 'icon' => 'th', 'badge' => '<span class="right badge badge-danger">New</span>'],
         ['label' => 'Yii2 PROVIDED', 'header' => true],
         ['label' => 'Gii',  'icon' => 'file-code', 'url' => ['/gii'], 'target' => '_blank'],
         ['label' => 'Debug', 'icon' => 'bug', 'url' => ['/debug'], 'target' => '_blank'],
         ['label' => 'Important', 'iconStyle' => 'far', 'iconClassAdded' => 'text-danger'],
         ['label' => 'Warning', 'iconClass' => 'nav-icon far fa-circle text-warning'],
     ]
])

?>

    <div class="sidebar-footer border-top d-none d-md-flex">
        <button class="sidebar-toggler" type="button" data-coreui-toggle="unfoldable"></button>
    </div>
</div>
