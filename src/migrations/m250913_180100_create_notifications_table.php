<?php

use yii\db\Migration;

/**
 * Tabela de notificações individuais (por destinatário).
 */
class m250913_180100_create_notifications_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // MariaDB/MySQL
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%notifications}}', [
            'id'                      => $this->primaryKey(),
            'group_id'                => $this->integer()->null(),
            'recipient_id'            => $this->integer()->notNull(), // id do usuário/paciente
            'recipient_type'          => $this->string(16)->notNull()->defaultValue('user'), // 'user'|'patient'
            'type'                    => $this->string(32)->defaultValue('system'),
            'description'             => $this->string(255)->notNull(),
            'content'                 => $this->text()->null(),
            'url'                     => $this->string(512)->null(),
            'status'                  => $this->tinyInteger()->notNull()->defaultValue(0), // 0=unread, 1=read
            'read_at'                 => $this->dateTime()->null(),
            'notification_message_id' => $this->integer()->null(), // pode ser nulo
            'created_at'              => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $tableOptions);

        // Índices usuais de leitura
        $this->createIndex('idx_notif_recipient', '{{%notifications}}', ['recipient_type', 'recipient_id']);
        $this->createIndex('idx_notif_status',    '{{%notifications}}', ['status']);
        $this->createIndex('idx_notif_group',     '{{%notifications}}', ['group_id']);
        $this->createIndex('idx_notif_created',   '{{%notifications}}', ['created_at']);

        // FK para notification_messages (SET NULL, CASCADE)
        $this->addForeignKey(
            'fk_notifications_message',
            '{{%notifications}}',
            'notification_message_id',
            '{{%notification_messages}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        // Remover FK antes da tabela
        try { $this->dropForeignKey('fk_notifications_message', '{{%notifications}}'); } catch (\Throwable $e) {}
        $this->dropTable('{{%notifications}}');
    }
}
