<?php

use yii\db\Migration;

/**
 * m251008_153000_create_trigger_tables
 * ------------------------------------
 * Creates tables for automated triggers (like Zabbix).
 */
class m251008_153000_create_trigger_tables extends Migration
{
    public function safeUp()
    {
        // === Main table: triggers ===
        $this->createTable('{{%triggers}}', [
            'id' => $this->primaryKey(),
            'group_id' => $this->integer()->null(),
            'name' => $this->string(128)->notNull(),
            'model_class' => $this->string(255)->notNull(), // Target model
            'expression' => $this->text()->notNull(),        // eval() condition
            'action_type' => $this->string(64)->notNull(),   // notify | call | webhook | command
            'action_target' => $this->string(255)->null(),   // notification key, method name, URL, etc.
            'cooldown_seconds' => $this->integer()->defaultValue(0),
            'last_triggered_at' => $this->dateTime()->null(),
            'enabled' => $this->boolean()->defaultValue(true),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // === Log table: trigger_logs ===
        $this->createTable('{{%trigger_logs}}', [
            'id' => $this->primaryKey(),
            'trigger_id' => $this->integer()->notNull(),
            'model_class' => $this->string(255)->notNull(),
            'model_id' => $this->integer()->null(),
            'executed_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'message' => $this->string(512)->null(),
            'success' => $this->boolean()->defaultValue(true),
        ]);

        $this->addForeignKey(
            'fk_trigger_logs_trigger_id',
            '{{%trigger_logs}}',
            'trigger_id',
            '{{%triggers}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_trigger_logs_trigger_id', '{{%trigger_logs}}');
        $this->dropTable('{{%trigger_logs}}');
        $this->dropTable('{{%triggers}}');
    }
}
