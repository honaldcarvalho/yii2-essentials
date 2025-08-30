<?php

use yii\db\Migration;

class m250829_000003_seed_master_roles_templates extends Migration
{
    public function safeUp()
    {
        $table = '{{%roles_templates}}';

        $this->batchInsert($table,
            ['level','controller','actions','origin','status'],
            [
                ['master','croacworks\\essentials\\controllers\\AuthorizationController','','*',1],
                ['master','croacworks\\essentials\\controllers\\CommonController','get-fields;get-model;get-models;order-menu;remove;remove-model;save-model;status;status-model','*',1],
                ['master','croacworks\\essentials\\controllers\\ConfigurationController','clone;clone-model;create;delete;get-fields;get-model;get-models;index;preview;remove;remove-model;save-model;status;status-model;update;view','*',1],
                ['master','croacworks\\essentials\\controllers\\EmailServiceController','create;delete;index;test;update;view','*',1],
                ['master','croacworks\\essentials\\controllers\\FileController','delete;delete-files;index;move;open;remove-file;view','*',1],
                ['master','croacworks\\essentials\\controllers\\FolderController','add;clone;create;delete;edit;get-fields;get-model;get-models;index;remove;remove-model;save-model;show;status;status-model;update;view','*',1],
                ['master','croacworks\\essentials\\controllers\\GroupController','create;delete;get-fields;get-model;get-models;index;order-menu;remove;remove-model;save-model;status;status-model;update;view','*',1],
                ['master','croacworks\\essentials\\controllers\\LanguageController','create;delete;index;update;view','*',1],
                ['master','croacworks\\essentials\\controllers\\LicenseController','create;delete;index;update;view','*',1],
                ['master','croacworks\\essentials\\controllers\\LicenseTypeController','create;delete;index;update;view','*',1],
                ['master','croacworks\\essentials\\controllers\\LogController','create;delete;index;update;view','*',1],
                ['master','croacworks\\essentials\\controllers\\MenuController','auto-add;clone;create;delete;get-model;index;order-menu;remove-model;save-model;update;view','*',1],
                ['master','croacworks\\essentials\\controllers\\MessageController','create;del;index;update;view','*',1],
                ['master','croacworks\\essentials\\controllers\\NotificationMessageController','create;delete;index;status;update;view','*',1],
                ['master','croacworks\\essentials\\controllers\\PageController','clone;create;delete;index;show;status;update;view','*',1],
                ['master','croacworks\\essentials\\controllers\\PageHeaderController','create;delete;index;update;view','*',1],
                ['master','croacworks\\essentials\\controllers\\RoleController','create;delete;get-actions;index;remove;status;update;view','*',1],
                ['master','croacworks\\essentials\\controllers\\RoleTemplateController','create;delete;get-actions;index;remove;status;update;view','*',1],
                ['master','croacworks\\essentials\\controllers\\SiteController','ckeditor;dashboard;export;index;login;logout;request-password-reset;resend-verification-email;reset-password;s;verify-email','*',1],
                ['master','croacworks\\essentials\\controllers\\SourceMessageController','add-translation;clone;create;delete;index;remove;remove-model;save-model;status;status-model;update;view','*',1],
                ['master','croacworks\\essentials\\controllers\\StorageController','attach;delete;delete-file;download;index;info;link;open;remove;save;spreadsheet;upload','*',1],
                ['master','croacworks\\essentials\\controllers\\UserController','add-group;change-lang;change-theme;clone;create;delete;edit;get-model;get-models;index;profile;remove;remove-group;remove-model;save-model;status;status-model;update;view','*',1],
                ['master','croacworks\\essentials\\controllers\\UtilController','get-model;get-models;remove-model;save-model;save-models','*',1],
            ]
        );
    }

    public function safeDown()
    {
        $this->delete('{{%roles_templates}}', ['level' => 'master']);
    }
}
