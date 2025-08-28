<?php

use croacworks\essentials\widgets\CoreUiMenu;

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