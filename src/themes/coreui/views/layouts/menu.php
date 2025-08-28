<?php

use croacworks\essentials\controllers\CommonController;
use croacworks\essentials\models\Configuration;
use croacworks\essentials\widgets\MenuAdminLte;

// Pré-existentes do seu código:
$config     = Configuration::get();
$assetDir   = CommonController::getAssetsDir(); // ajuste se seu tema definir outro helper
$name_split = explode(' ', Yii::$app->user->identity->username);
$name_user  = $name_split[0] . (isset($name_split[1]) ? ' ' . end($name_split) : '');

?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="<?=Yii::getAlias('/');?>" class="brand-link">
        <span class="brand-text font-weight-light"><?= $config->title ?></span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image user-image">
                <?php if(Yii::$app->user->identity->profile->file):?>
                    <img class='brand-image img-circle elevation-2' src="<?= Yii::$app->user->identity->profile->file->url; ?>" style='width:32px; opacity: .8' />
                <?php else:?>
                        <i class="fas fa-user-circle img-circle elevation-2" alt="User Image"></i>
                <?php endif;?>
            </div>
            <div class="info">
                <?= yii\helpers\Html::a($name_user, ['/user/profile', 'id' =>Yii::$app->user->identity->id],["class"=>"d-block"]) ?><br>
            </div>
        </div>

        <!-- SidebarSearch Form -->
        <!-- href be escaped -->
        <div class="form-inline">
            <div class="input-group" data-widget="sidebar-search">
                <input class="form-control form-control-sidebar" type="search" placeholder="<?=Yii::t('app','Search')?>" aria-label="<?=Yii::t('app','Search')?>">
                <div class="input-group-append">
                    <button class="btn btn-sidebar">
                        <i class="fas fa-search fa-fw"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
<?php

echo MenuAdminLte::widget([
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
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>