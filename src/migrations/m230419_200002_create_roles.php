<?php

use yii\db\Migration;

class m230419_200002_create_roles extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%roles}}', [
            'id' => $this->primaryKey(),
            'group_id' => $this->integer()->null(),
            'user_id' => $this->integer()->null(),
            'name' => $this->string(120)->null(),
            'controller' => $this->string(255)->notNull(), // FQCN
            'action' => $this->string(64)->notNull(),      // ex.: index|view|* 
            'status' => $this->tinyInteger()->notNull()->defaultValue(1),
            'created_at' => $this->dateTime()->defaultValue(new \yii\db\Expression('NOW()')),
            'updated_at' => $this->timestamp()->defaultValue(null)->append('ON UPDATE CURRENT_TIMESTAMP'),
        ]);
        
        $this->addForeignKey(
            'fk-roles-user_id',
            'roles',
            'user_id',
            'users',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-roles-group_id',
            'roles',
            'group_id',
            'groups',
            'id',
            'CASCADE'
        );
    }
    public function safeDown()
    {
        $this->dropTable('{{%roles}}');
    }
}
