<?php

use yii\db\Migration;

class m250719_000001_create_dynamic_forms_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable('dynamic_forms', [
            'id' => $this->bigPrimaryKey(),
            'group_id' => $this->integer()->null(),
            'name' => $this->string()->notNull(),
            'description' => $this->string(),
            'status' => $this->boolean()->defaultValue(true),
        ]);

        $this->addForeignKey(
            'fk-dynamic_forms-group_id',
            'dynamic_forms',
            'group_id',
            'groups',
            'id',
            'RESTRICT'
        );
    }

    public function safeDown()
    {
        $this->dropTable('dynamic_forms');
    }
}