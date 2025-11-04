<?php

use yii\db\Migration;

/**
 * Inserts sys_menus rows resolving parent_id by parent's label (no fixed IDs).
 * Idempotent: re-runs won't duplicate existing labels.
 */
class m250829_000001_seed_sys_menus extends Migration
{
    private string $table = '{{%sys_menus}}';

    /** Labels we insert (for safeDown), ordered parent-first; children later */
    private array $items = [
        // Top level parents
        ['label' => 'System',       'parent' => null, 'icon' => 'fas fa-cogs',        'icon_style' => 'fas', 'url' => '#',                 'order' => 9,    'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => null, 'action' => null,   'active' => null,   'visible' => null],
        ['label' => 'Development',  'parent' => null, 'icon' => 'fas fa-file-code',   'icon_style' => 'fas', 'url' => '#',                 'order' => 10,   'only_admin' => 1, 'status' => 1, 'show' => 1, 'controller' => null, 'action' => null,   'active' => null,   'visible' => null],
        ['label' => 'Dashboard',    'parent' => null, 'icon' => 'fas fa-tachometer-alt','icon_style'=>'fas', 'url' => '/site/dashboard',    'order' => 1,    'only_admin' => 1, 'status' => 1, 'show' => 1, 'controller' => 'croacworks\\essentials\\controllers\\SiteController', 'action' => 'dashboard', 'active' => 'dashboard', 'visible' => 'croacworks\\essentials\\controllers\\SiteController;dashboard'],

        // Children of System
        ['label' => 'Menus',           'parent' => 'System', 'icon' => 'fas fa-bars',            'icon_style' => 'fas', 'url' => '/menu/index',             'order' => 2,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => 'croacworks\\essentials\\controllers\\MenuController',           'action' => 'index', 'active' => 'menu',          'visible' => 'index'],
        ['label' => 'Report Templates','parent' => 'System', 'icon' => 'fas fa-print',           'icon_style' => 'fas', 'url' => '/report-template/index',   'order' => 2,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => 'croacworks\\essentials\\controllers\\ReportTemplateController','action' => 'index', 'active' => 'report-template',          'visible' => 'index'],
        ['label' => 'Configurations',  'parent' => 'System', 'icon' => 'fas fa-clipboard-check', 'icon_style' => 'fas', 'url' => '/configuration/index',     'order' => 2,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => 'croacworks\\essentials\\controllers\\ConfigurationController','action' => 'index', 'active' => null,          'visible' => 'index'],
        ['label' => 'Authentication',  'parent' => 'System', 'icon' => 'fas fa-key',             'icon_style' => 'fas', 'url' => '#',                        'order' => 3,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => null,                                           'action' => null,   'active' => null,          'visible' => null],
        ['label' => 'Dinamic Pages',   'parent' => 'System', 'icon' => 'fas fa-copy',            'icon_style' => 'fas', 'url' => '#',                        'order' => 3,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => null,                                           'action' => null,   'active' => null,          'visible' => null],
        ['label' => 'Storage',         'parent' => 'System', 'icon' => 'fas fa-hdd',             'icon_style' => 'fas', 'url' => '#',                        'order' => 4,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => null,                                           'action' => null,   'active' => null,          'visible' => null],
        ['label' => 'Notifications',   'parent' => 'System', 'icon' => 'fas fa-comment-alt',     'icon_style' => 'fas', 'url' => '#',                        'order' => 5,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => null,                                           'action' => null,   'active' => null,          'visible' => null],
        ['label' => 'Translations',    'parent' => 'System', 'icon' => 'fas fa-globe',           'icon_style' => 'fas', 'url' => '#',                        'order' => 6,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => null,                                           'action' => null,   'active' => null,          'visible' => null],
        ['label' => 'Logs',            'parent' => 'System', 'icon' => 'fas fa-keyboard',        'icon_style' => 'fas', 'url' => '#',                        'order' => 1000,'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => '',                                              'action' => '',      'active' => null,          'visible' => 'index'],

        // Children of Development
        ['label' => 'Debug',           'parent' => 'Development', 'icon' => 'fas fa-file-code',  'icon_style' => 'fas', 'url' => 'debug/default/view',       'order' => 0,   'only_admin' => 1, 'status' => 1, 'show' => 1, 'controller' => 'app\\controllers\\DebugController',          'action' => 'default', 'active' => null,        'visible' => null],
        ['label' => 'Gii',             'parent' => 'Development', 'icon' => 'fas fa-file-code',  'icon_style' => 'fas', 'url' => '/gii',                     'order' => 1,   'only_admin' => 1, 'status' => 1, 'show' => 1, 'controller' => 'app\\controllers\\GiiController',            'action' => '*',       'active' => null,        'visible' => null],

        // Children of Authentication
        ['label' => 'Groups',          'parent' => 'Authentication', 'icon' => 'fas fa-users',  'icon_style' => 'fas', 'url' => '/group/index',             'order' => 0,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => 'croacworks\\essentials\\controllers\\GroupController',       'action' => 'index', 'active' => null,          'visible' => 'index'],
        ['label' => 'Users',           'parent' => 'Authentication', 'icon' => 'fas fa-user',   'icon_style' => 'fas', 'url' => '/user/index',              'order' => 1,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => 'croacworks\\essentials\\controllers\\UserController',        'action' => 'index', 'active' => null,          'visible' => 'index'],
        ['label' => 'Roles',           'parent' => 'Authentication', 'icon' => 'fas fa-person-booth','icon_style'=>'fas','url' => '/role/index',          'order' => 2,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => 'croacworks\\essentials\\controllers\\RoleController',        'action' => '',      'active' => 'role',       'visible' => 'croacworks\\essentials\\controllers\\RoleController;index'],
        ['label' => 'License Types',   'parent' => 'Authentication', 'icon' => 'fas fa-certificate','icon_style'=>'fas','url' => '/license-type/index',  'order' => 5,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => 'croacworks\\essentials\\controllers\\LicenseTypeController',  'action' => 'index', 'active' => null,          'visible' => 'index'],
        ['label' => 'Licenses',        'parent' => 'Authentication', 'icon' => 'fas fa-certificate','icon_style'=>'fas','url' => '/license/index',       'order' => 6,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => 'croacworks\\essentials\\controllers\\LicenseController',     'action' => 'index', 'active' => null,          'visible' => 'index'],
        ['label' => 'Role Templates',  'parent' => 'Authentication', 'icon' => 'fas fa-puzzle-piece','icon_style'=>'fas','url' => '/role-template/index','order' => 2,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => 'croacworks\\essentials\\controllers\\RoleTemplateController', 'action' => '', 'active' => 'role-template','visible' => 'croacworks\\essentials\\controllers\\RoleTemplateController;index'],

        // Children of Dinamic Pages
        ['label' => 'Sections',        'parent' => 'Dinamic Pages', 'icon' => '',                'icon_style' => 'fas', 'url' => '/page-section/index',     'order' => 3,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => 'croacworks\\essentials\\controllers\\PageSectionController', 'action' => 'index', 'active' => 'page-section', 'visible' => 'croacworks\\essentials\\controllers\\PageSectionController;index'],
        ['label' => 'Pages',           'parent' => 'Dinamic Pages', 'icon' => 'fas fa-file',     'icon_style' => 'fas', 'url' => '/page/index',             'order' => 5,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => 'croacworks\\essentials\\controllers\\PageController',        'action' => 'index', 'active' => null,          'visible' => 'index'],
        ['label' => 'Tags',            'parent' => 'Tags',          'icon' => 'fas fa-tags',     'icon_style' => 'fas', 'url' => '/tag/index',              'order' => 5,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => 'croacworks\\essentials\\controllers\\TagController',         'action' => 'index', 'active' => null,          'visible' => 'index'],

        // Children of Storage
        ['label' => 'Folders',         'parent' => 'Storage', 'icon' => 'fas fa-folder',         'icon_style' => 'fas', 'url' => '/folder/index',           'order' => 0,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => 'croacworks\\essentials\\controllers\\FolderController',     'action' => 'index', 'active' => null,          'visible' => 'index'],
        ['label' => 'Files',           'parent' => 'Storage', 'icon' => 'fas fa-file',           'icon_style' => 'fas', 'url' => '/file/index',             'order' => 0,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => 'croacworks\\essentials\\controllers\\FileController',       'action' => 'index', 'active' => null,          'visible' => 'index'],

        // Children of Notifications
        ['label' => 'Messages',        'parent' => 'Notifications', 'icon' => 'fas fa-comment-dots','icon_style'=>'fas','url' => '/notification/index',  'order' => 0,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => 'croacworks\\essentials\\controllers\\NotificationController', 'action' => '', 'active' => 'index', 'visible' => 'croacworks\\essentials\\controllers\\NotificationController;index'],
        ['label' => 'Broadcast',       'parent' => 'Notifications', 'icon' => 'fas fa-broadcast-tower','icon_style'=>'fas','url' => '/notification/broadcast','order'=> 7,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => 'croacworks\\essentials\\controllers\\NotificationController', 'action' => '', 'active' => 'croacworks\\essentials\\controllers\\NotificationController;broadcast', 'visible' => 'croacworks\\essentials\\controllers\\NotificationController;broadcast'],

        // Children of Translations
        ['label' => 'Languages',       'parent' => 'Translations', 'icon' => 'fas fa-language',  'icon_style' => 'fas', 'url' => '/language/index',        'order' => 0,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => 'croacworks\\essentials\\controllers\\LanguageController',   'action' => 'index', 'active' => null,          'visible' => 'index'],
        ['label' => 'Source Messages', 'parent' => 'Translations', 'icon' => 'fas fa-comment',   'icon_style' => 'fas', 'url' => '/source-message/index',   'order' => 2,   'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => 'croacworks\\essentials\\controllers\\SourceMessageController','action' => 'index', 'active' => null,          'visible' => 'index'],

        // Children of Logs
        ['label' => 'Geral',           'parent' => 'Logs', 'icon' => '',                         'icon_style' => 'fas', 'url' => '/log/index',              'order' => 1000,'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => 'croacworks\\essentials\\controllers\\LogController',        'action' => 'index', 'active' => 'index',       'visible' => 'index'],
        ['label' => 'Autentication',   'parent' => 'Logs', 'icon' => 'fas fa-sign-in-alt',       'icon_style' => 'fas', 'url' => '/log/auth',               'order' => 1000,'only_admin' => 0, 'status' => 1, 'show' => 1, 'controller' => 'croacworks\\essentials\\controllers\\LogController',        'action' => 'auth',  'active' => 'auth',        'visible' => 'auth'],

    ];

    public function safeUp()
    {
        // resolve & insert in waves while parents exist
        $pending = $this->items;
        $resolved = [];

        while (!empty($pending)) {
            $progress = false;

            foreach ($pending as $k => $row) {
                $parentId = null;
                if (!empty($row['parent'])) {
                    $parentId = $this->findIdByLabel($row['parent']);
                    if ($parentId === null) {
                        // parent not created yet; skip for next wave
                        continue;
                    }
                }

                $id = $this->findIdByLabel($row['label']);
                if ($id === null) {
                    $this->insert($this->table, [
                        'parent_id'   => $parentId,
                        'label'       => $row['label'],
                        'icon'        => $row['icon'],
                        'icon_style'  => $row['icon_style'],
                        'url'         => $row['url'],
                        'order'       => $row['order'],
                        'only_admin'  => $row['only_admin'],
                        'status'      => $row['status'],
                        'show'        => $row['show'],
                        'controller'  => $row['controller'],
                        'action'      => $row['action'],
                        'active'      => $row['active'],
                        'visible'     => $row['visible'],
                    ]);
                } else {
                    // ensure correct parent if needed (optional small sync)
                    if ($parentId !== null) {
                        $this->update($this->table, ['parent_id' => $parentId], ['id' => $id]);
                    }
                }

                $resolved[] = $row['label'];
                unset($pending[$k]);
                $progress = true;
            }

            if (!$progress) {
                // Cyclic or missing parent names
                throw new \RuntimeException('Could not resolve some menu parents. Check parent labels and order.');
            }
        }
    }

    public function safeDown()
    {
        // delete in reverse dependency order (children before parents)
        $labels = array_column($this->items, 'label');
        $labels = array_reverse($labels);

        foreach ($labels as $label) {
            $id = $this->findIdByLabel($label);
            if ($id !== null) {
                $this->delete($this->table, ['id' => $id]);
            }
        }
    }

    private function findIdByLabel(string $label): ?int
    {
        $row = (new \yii\db\Query())
            ->select(['id'])
            ->from($this->table)
            ->where(['label' => $label])
            ->one($this->db);

        return $row ? (int)$row['id'] : null;
    }
}
