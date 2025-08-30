<?php

use yii\db\Migration;

class m230419_200002_create_roles extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%roles}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(),
            'user_id' => $this->integer(),
            'group_id' => $this->integer(),
            'controller' => $this->text()->notNull(), // FQCN com namespace
            'actions' => $this->text(),
            'origin' => $this->text()->notNull()->defaultValue('*'),
            'created_at' => $this->dateTime()->defaultValue(new \yii\db\Expression('NOW()')),
            'updated_at' => $this->dateTime()->defaultValue(new \yii\db\Expression('NOW()')),
            'status' => $this->integer()->defaultValue(1)
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
