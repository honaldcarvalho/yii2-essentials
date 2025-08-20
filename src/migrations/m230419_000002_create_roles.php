<?php

use yii\db\Migration;

class m230419_000002_create_roles extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%roles}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(120)->null(),
            'controller' => $this->string(255)->notNull(), // FQCN
            'action' => $this->string(64)->notNull(),      // ex.: index|view|* 
            'group_id' => $this->integer()->null(),
            'status' => $this->tinyInteger()->notNull()->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_roles_ctrl_act_grp', '{{%roles}}', ['controller','action','group_id','status']);
    }
    public function safeDown()
    {
        $this->dropTable('{{%roles}}');
    }
}
