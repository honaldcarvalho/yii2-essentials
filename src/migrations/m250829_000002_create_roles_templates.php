<?php

use yii\db\Migration;

class m250829_000002_create_roles_templates extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

        $this->createTable('{{%roles_templates}}', [
            'id'         => $this->primaryKey()->unsigned(),
            'level'      => "ENUM('master','admin','user') NOT NULL",
            'controller' => $this->string(255)->notNull(),
            'actions'    => $this->text()->null(),
            'origin'     => $this->string(50)->notNull()->defaultValue('*'),
            'status'     => $this->boolean()->notNull()->defaultValue(1),
        ], $tableOptions);

        $this->createIndex('idx_roles_templates_level', '{{%roles_templates}}', 'level');
        $this->createIndex('idx_roles_templates_controller', '{{%roles_templates}}', 'controller');

    }

    public function safeDown()
    {
        $this->dropIndex('idx_roles_templates_controller', '{{%roles_templates}}');
        $this->dropIndex('idx_roles_templates_level', '{{%roles_templates}}');
        $this->dropTable('{{%roles_templates}}');
    }
}
