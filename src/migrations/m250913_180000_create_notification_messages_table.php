<?php

use yii\db\Migration;

/**
 * Tabela de modelos/descrições de eventos que originam notificações.
 */
class m250913_180000_create_notification_messages_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // MariaDB/MySQL
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%notification_messages}}', [
            'id'          => $this->primaryKey(),
            'model'       => $this->string(128)->notNull(),
            'model_id'    => $this->integer()->null(),
            'model_field' => $this->string(128)->notNull(),
            'description' => $this->string(255)->notNull(),
            'type'        => $this->string(32)->null(),      // ex: system, info, warning
            'group_id'    => $this->integer()->null(),
            'created_at'  => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at'  => $this->dateTime()->null(),
        ], $tableOptions);

        // Índices
        $this->createIndex('idx_nmsg_group', '{{%notification_messages}}', ['group_id']);
        $this->createIndex('idx_nmsg_model', '{{%notification_messages}}', ['model', 'model_id']);
        $this->createIndex('idx_nmsg_type',  '{{%notification_messages}}', ['type']);
    }

    public function safeDown()
    {
        $this->dropTable('{{%notification_messages}}');
    }
}
