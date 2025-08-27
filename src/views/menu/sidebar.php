<?php

use croacworks\essentials\controllers\CommonController;
use croacworks\essentials\controllers\AuthController;
use croacworks\essentials\models\SysMenu;
use croacworks\essentials\models\Configuration;
use croacworks\essentials\widgets\Menu as WidgetsMenu;
use yii\web\View;

if(Yii::$app->user->isGuest){
    return false;
}
$params  = Configuration::get();
$script = <<< JS
JS;
$this->registerJS($script);


if(!Yii::$app->user->isGuest){
    $name_split = explode(' ',Yii::$app->user->identity->fullname);
    $name_user = $name_split[0].(isset($name_split[1]) ? ' ' .end($name_split) : '');
    $controller_id = Yii::$app->controller->id;
    $group = Yii::$app->session->get('group');
}
$assetsDir =  CommonController::getAssetsDir();
if(!empty($params->file_id) && $params->file != null){
    $url = Yii::getAlias('@web').$params->file->urlThumb; 
    $login_image = "<img alt='{$params->title}' class='brand-image img-circle elevation-3' src='{$url}' style='opacity: .8' />";
}else{
    $login_image = "<img src='{$assetsDir}/img/croacworks-logo-hq.png' alt='{$params->title}' class='brand-image elevation-3' style='opacity: .8'>";
}
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="<?=Yii::getAlias('/');?>" class="brand-link">
        <?= $login_image ?>
        <span class="brand-text font-weight-light"><?= $params->title ?></span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image user-image">
                <?php if(Yii::$app->user->identity->file):?>
                    <img class='brand-image img-circle elevation-2' src="<?= Yii::$app->user->identity->file->url; ?>" style='width:32px; opacity: .8' />
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

                function getNodes($controller_id,$id = null){

                    $items = SysMenu::find()->where(['menu_id'=>$id,'status'=>true])->orderBy(['order'=>SORT_ASC])->all();

                    $nodes = [];
                    foreach($items as $item){

                        if($item['url'] == '#' || ($item['url'] != '#' && $item['menu_id'] == null)) {

                            $visible_parts = explode(';',$item['visible']);
                            $isVisible = true;
                            $item_nodes = getNodes($controller_id,$item['id']);

                            if(empty($item_nodes) && $item['url'] == '#'){
                                $isVisible = false;
                            }else {

                                if(count($visible_parts) > 1){
                                    $isVisible =  AuthController::verAuthorization($visible_parts[0],$visible_parts[1],null,$item['path']);
                                }else if(count($visible_parts) === 1){
                                    //verify if someone item is visible, case yes, show menu item 
                                    foreach($item_nodes as $item_node){
                                        if($item_node['visible']){
                                            $isVisible = true;
                                            break;
                                        }
                                        $isVisible = false;
                                    }
                                }else{
                                    $isVisible = false;
                                }
                            }

                            $node = [
                                'label' => Yii::t('app', $item['label']),
                                'icon'=> "{$item['icon']}",
                                'iconStyle'=> "{$item['icon_style']}",
                                'url' => ["{$item['url']}"],
                                'visible'=> $isVisible,
                                'items'=> $item_nodes,
                            ];

                            if($item['url'] != '#') {
                                $node['active'] = ($controller_id == "{$item['active']}") || ($controller_id."/".Yii::$app->controller->action->id  == "{$item['active']}");
                            }

                            if(!$item['only_admin'] || $item['only_admin'] &&  AuthController::isAdmin()) {
                                $nodes[] = $node;
                            }
                                    
                        }else{
                            $visible_parts = explode(';',$item['visible']);
                            $isVisible = true;
                            
                            if(count($visible_parts) > 1){
                                $isVisible =  AuthController::verAuthorization($visible_parts[0],$visible_parts[1],null,$item['path']);
                            }else if(empty($visible_parts)){
                                $isVisible = false;
                            }

                            if(!$item['only_admin'] || $item['only_admin'] &&  AuthController::isAdmin()) {
                                $nodes[] = [
                                    'label' => Yii::t('app', $item['label']),
                                    'icon'=> "{$item['icon']}",
                                    'iconStyle'=> "{$item['icon_style']}",
                                    'url' => ["{$item['url']}"],
                                    'visible'=> $isVisible,
                                    'active'=>($controller_id == "{$item['active']}") || ($controller_id."/".Yii::$app->controller->action->id  == "{$item['active']}")
                                ];
                            }
                        }
                    }

                    return $nodes;

                }

                $nodes = getNodes(Yii::$app->controller->id);

                echo WidgetsMenu::widget([

                    "options" =>   [
                        'class' => 'nav nav-pills nav-sidebar flex-column nav-child-indent',
                        'data-widget' => 'treeview',
                        'role' => 'menu',
                        'data-accordion' => 'false'
                    ],

                    'items'=> array_merge($nodes,[['label' => 'Logout','icon'=>'fas fa-sign-out-alt', 'url' => ['/site/logout']]])
                ]);

            ?>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>