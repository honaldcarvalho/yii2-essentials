<?php

use yii\db\Migration;

class m250829_000001_create_roles_templates extends Migration
{
    public function safeUp()
    {

        $this->batchInsert('{{%roles_templates}}',
            ['level','controller','actions','origin','status'],
            [
                ['admin','croacworks\\essentials\\controllers\\SiteController','dashboard;index;logout','*',1],
                ['admin','croacworks\\essentials\\controllers\\UserController','index;view;update','*',1],
                ['admin','croacworks\\essentials\\controllers\\MenuController','index;view;update','*',1],
                ['admin','croacworks\\essentials\\controllers\\ConfigurationController','index;view;update','*',1],
                ['admin','croacworks\\essentials\\controllers\\RoleController','index;view;get-actions;update','*',1],
                ['admin','croacworks\\essentials\\controllers\\StorageController','index;open;download;upload;delete-file','*',1],
            ]
        );

        // Inserts iniciais para USER
        $this->batchInsert('{{%roles_templates}}',
            ['level','controller','actions','origin','status'],
            [
                ['user','croacworks\\essentials\\controllers\\SiteController','dashboard;index;logout','*',1],
                ['user','croacworks\\essentials\\controllers\\UserController','profile;update','*',1],
                ['user','croacworks\\essentials\\controllers\\StorageController','index;open;download','*',1],
            ]
        );

    }

    public function safeDown()
    {
        return true;
    }
}
