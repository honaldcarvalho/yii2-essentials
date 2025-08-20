<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%params}}`.
 */
class m230911_192840_create_configurations_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%configurations}}', [
            'id' => $this->primaryKey(),
            'description' => $this->string()->notNull(),
            'language_id' => $this->integer()->notNull()->defaultValue(1),//'en-US'
            'group_id' => $this->integer()->notNull()->defaultValue(3),//clients
            'file_id' => $this->integer(),
            'email_service_id' => $this->integer(),
            'email' => $this->string()->notNull(),
            'host' => $this->string()->notNull(),
            'title' => $this->string()->notNull(),
            'slogan' => $this->string()->notNull(),
            'bussiness_name' => $this->string()->notNull(),
            'created_at' => $this->dateTime()->defaultValue(new \yii\db\Expression('NOW()')),
            'updated_at' => $this->timestamp()->defaultValue(null)->append('ON UPDATE CURRENT_TIMESTAMP'),
            'status'=> $this->integer()->defaultValue(1),
            'logging'=> $this->integer()->defaultValue(1)
        ]);

    }

    /**

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%configurations}}');
    }
}
