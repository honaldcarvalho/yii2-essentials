<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%access_log}}`.
 */
class m250928_180100_create_access_log_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('access_log', [
            'id' => $this->primaryKey(),
            'ip_address' => $this->string(45)->notNull(),
            'url' => $this->text()->notNull(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('access_log');
    }
}