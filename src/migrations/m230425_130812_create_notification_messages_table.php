<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%notifications}}`.
 */
class m230425_130812_create_notification_messages_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%notification_messages}}', [
            'id' => $this->primaryKey(),
            'group_id' => $this->integer(),
            'description' => $this->string()->notNull(),
            'type' => "ENUM('success','warning','danger','default','info')",
            'message' => 'LONGTEXT',
            'created_at' => $this->dateTime()->defaultValue(new \yii\db\Expression('NOW()')),
            'updated_at' => $this->timestamp()->defaultValue(null)->append('ON UPDATE CURRENT_TIMESTAMP'),
            'status' => $this->integer()->defaultValue(1),
        ]);

        $this->addForeignKey(
            'fk-notification_messages-group_id',
            'notification_messages',
            'group_id',
            'groups',
            'id',
            'RESTRICT'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%notification_messages}}');
    }
}
