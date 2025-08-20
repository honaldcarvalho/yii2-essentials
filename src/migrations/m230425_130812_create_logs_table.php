<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%logs}}`.
 */
class m230425_130812_create_logs_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%logs}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'controller' => $this->string(50),
            'action' => $this->string(50),
            'ip' => $this->string(50),
            'device' => $this->string(150),
            'data' => 'LONGTEXT',
            'created_at' => $this->dateTime()->defaultValue(new \yii\db\Expression('NOW()'))
        ]);

        $this->addForeignKey(
            'fk-logs-user_id',
            'logs',
            'user_id',
            'users',
            'id',
            'CASCADE'
        );

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%logs}}');
    }
}
