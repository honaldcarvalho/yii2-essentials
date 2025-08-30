<?php

use yii\db\Migration;

/**
 * Class m250829_000001_seed_admin_roles
 * Insere roles para o grupo Admin (group_id=2) com base nos controllers da extensão croacworks\essentials.
 * Ajuste o $adminGroupId se o ID do grupo Admin for diferente.
 */
class m250829_000001_seed_admin_roles extends Migration
{
    private int $adminGroupId = 2;
    private string $table = '{{%roles}}';

    public function safeUp()
    {
        $rows = [
            [
                'user_id' => null,
                'group_id' => 2,
                'controller' => 'croacworks\\essentials\\controllers\\ConfigurationController',
                'actions' => 'clone;create;delete;index;preview;update;view',
                'origin' => '*',
            ],
            [
                'user_id' => null,
                'group_id' => 2,
                'controller' => 'croacworks\\essentials\\controllers\\EmailServiceController',
                'actions' => 'create;delete;index;test;update;view',
                'origin' => '*',
            ],
            [
                'user_id' => null,
                'group_id' => 2,
                'controller' => 'croacworks\\essentials\\controllers\\FileController',
                'actions' => 'delete;delete-files;index;move;open;remove-file;view',
                'origin' => '*',
            ],
            [
                'user_id' => null,
                'group_id' => 2,
                'controller' => 'croacworks\\essentials\\controllers\\FolderController',
                'actions' => 'add;create;delete;edit;index;remove;show;update;view',
                'origin' => '*',
            ],
            [
                'user_id' => null,
                'group_id' => 2,
                'controller' => 'croacworks\\essentials\\controllers\\GroupController',
                'actions' => 'create;delete;index;update;view',
                'origin' => '*',
            ],
            [
                'user_id' => null,
                'group_id' => 2,
                'controller' => 'croacworks\\essentials\\controllers\\LanguageController',
                'actions' => 'create;delete;index;update;view',
                'origin' => '*',
            ],
            [
                'user_id' => null,
                'group_id' => 2,
                'controller' => 'croacworks\\essentials\\controllers\\LicenseController',
                'actions' => 'create;delete;index;update;view',
                'origin' => '*',
            ],
            [
                'user_id' => null,
                'group_id' => 2,
                'controller' => 'croacworks\\essentials\\controllers\\LicenseTypeController',
                'actions' => 'create;delete;index;update;view',
                'origin' => '*',
            ],
            [
                'user_id' => null,
                'group_id' => 2,
                'controller' => 'croacworks\\essentials\\controllers\\LogController',
                'actions' => 'create;delete;index;update;view',
                'origin' => '*',
            ],
            [
                'user_id' => null,
                'group_id' => 2,
                'controller' => 'croacworks\\essentials\\controllers\\MenuController',
                'actions' => 'create;delete;index;order-menu;update;view;get-model;save-model;remove-model',
                'origin' => '*',
            ],
            [
                'user_id' => null,
                'group_id' => 2,
                'controller' => 'croacworks\\essentials\\controllers\\MessageController',
                'actions' => 'create;del;index;update;view',
                'origin' => '*',
            ],
            [
                'user_id' => null,
                'group_id' => 2,
                'controller' => 'croacworks\\essentials\\controllers\\NotificationMessageController',
                'actions' => 'create;delete;index;update;view',
                'origin' => '*',
            ],
            [
                'user_id' => null,
                'group_id' => 2,
                'controller' => 'croacworks\\essentials\\controllers\\PageController',
                'actions' => 'clone;create;delete;index;show;update;view',
                'origin' => '*',
            ],
            [
                'user_id' => null,
                'group_id' => 2,
                'controller' => 'croacworks\\essentials\\controllers\\PageHeaderController',
                'actions' => 'create;delete;index;update;view',
                'origin' => '*',
            ],
            [
                'user_id' => null,
                'group_id' => 2,
                'controller' => 'croacworks\\essentials\\controllers\\RuleController',
                'actions' => 'create;delete;edit;index;update;view',
                'origin' => '*',
            ],
            [
                'user_id' => null,
                'group_id' => 2,
                'controller' => 'croacworks\\essentials\\controllers\\SiteController',
                'actions' => 'ckeditor;dashboard;export;index;login;logout;request-password-reset;resend-verification-email;reset-password;s;verify-email',
                'origin' => '*',
            ],
            [
                'user_id' => null,
                'group_id' => 2,
                'controller' => 'croacworks\\essentials\\controllers\\SourceMessageController',
                'actions' => 'add-translation;create;delete;index;update;view',
                'origin' => '*',
            ],
            [
                'user_id' => null,
                'group_id' => 2,
                'controller' => 'croacworks\\essentials\\controllers\\StorageController',
                'actions' => 'attach;delete;delete-file;download;index;info;link;open;remove;save;spreadsheet;upload',
                'origin' => '*',
            ],
            [
                'user_id' => null,
                'group_id' => 2,
                'controller' => 'croacworks\\essentials\\controllers\\UserController',
                'actions' => 'create;delete;edit;index;profile;remove-group;update',
                'origin' => '*',
            ]
        ];

        // Evita duplicatas (já existentes)
        foreach ($rows as $k => $r) {
            $exists = (new \yii\db\Query())
                ->from($this->table)
                ->where([
                    'group_id' => $this->adminGroupId,
                    'controller' => $r['controller'],
                ])
                ->exists($this->db);
            if ($exists) {
                unset($rows[$k]);
            }
        }

        if (!empty($rows)) {
            $this->batchInsert($this->table, ['user_id','group_id','controller','actions','origin'], array_map(function($r){
                return [$r['user_id'],$r['group_id'],$r['controller'],$r['actions'],$r['origin']];
            }, $rows));
        }
    }

    public function safeDown()
    {
        // Remove apenas os registros deste grupo e destes controllers
        $controllers = [
            'croacworks\\essentials\\controllers\\AuthorizationController',
            'croacworks\\essentials\\controllers\\CommonController',
            'croacworks\\essentials\\controllers\\ConfigurationController',
            'croacworks\\essentials\\controllers\\EmailServiceController',
            'croacworks\\essentials\\controllers\\FileController',
            'croacworks\\essentials\\controllers\\FolderController',
            'croacworks\\essentials\\controllers\\GroupController',
            'croacworks\\essentials\\controllers\\LanguageController',
            'croacworks\\essentials\\controllers\\LicenseController',
            'croacworks\\essentials\\controllers\\LicenseTypeController',
            'croacworks\\essentials\\controllers\\LogController',
            'croacworks\\essentials\\controllers\\MenuController',
            'croacworks\\essentials\\controllers\\MessageController',
            'croacworks\\essentials\\controllers\\NotificationMessageController',
            'croacworks\\essentials\\controllers\\PageController',
            'croacworks\\essentials\\controllers\\PageHeaderController',
            'croacworks\\essentials\\controllers\\RuleController',
            'croacworks\\essentials\\controllers\\SiteController',
            'croacworks\\essentials\\controllers\\SourceMessageController',
            'croacworks\\essentials\\controllers\\StorageController',
            'croacworks\\essentials\\controllers\\UserController',
            'croacworks\\essentials\\controllers\\UtilController',
        ];
        $this->delete($this->table, ['and',
            ['group_id' => $this->adminGroupId],
            ['in', 'controller', $controllers]
        ]);
    }
}
