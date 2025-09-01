<?php

use yii\db\Migration;

class m20250901_000001_license_soft_limit_and_active_sessions extends Migration
{
    public function safeUp()
    {
        // 1) Alterar licenses: heartbeat_seconds + max_users_override
        $this->addColumn('{{%licenses}}', 'heartbeat_seconds', $this->integer()->notNull()->defaultValue(600)->after('license_type_id'));
        $this->addColumn('{{%licenses}}', 'max_users_override', $this->integer()->null()->after('heartbeat_seconds'));

        // 2) Criar user_active_sessions
        $this->createTable('{{%user_active_sessions}}', [
            'id'           => $this->primaryKey(),
            'session_id'   => $this->string(64)->notNull(),
            'user_id'      => $this->integer()->notNull(),
            'group_id'     => $this->integer()->notNull(),
            'ip'           => $this->string(45)->null(),
            'user_agent'   => $this->string(255)->null(),
            'created_at'   => $this->dateTime()->notNull(),
            'last_seen_at' => $this->dateTime()->notNull(),
            'is_active'    => $this->boolean()->notNull()->defaultValue(1),
        ]);

        // Índices/uniques para performance e upsert
        $this->createIndex('ux_user_active_sessions_session', '{{%user_active_sessions}}', ['session_id'], true);
        $this->createIndex('ix_user_active_sessions_group_active', '{{%user_active_sessions}}', ['group_id', 'is_active']);
        $this->createIndex('ix_user_active_sessions_user_group', '{{%user_active_sessions}}', ['user_id', 'group_id']);
        $this->createIndex('ix_user_active_sessions_last_seen', '{{%user_active_sessions}}', ['last_seen_at']);

        // (Opcional) FKs se quiser estrito; se preferir solto, comente.
        // $this->addForeignKey('fk_uas_user', '{{%user_active_sessions}}', 'user_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');
        // $this->addForeignKey('fk_uas_group', '{{%user_active_sessions}}', 'group_id', '{{%groups}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown()
    {
        // Reverter
        // $this->dropForeignKey('fk_uas_group', '{{%user_active_sessions}}');
        // $this->dropForeignKey('fk_uas_user', '{{%user_active_sessions}}');
        $this->dropTable('{{%user_active_sessions}}');

        $this->dropColumn('{{%licenses}}', 'max_users_override');
        $this->dropColumn('{{%licenses}}', 'heartbeat_seconds');
    }
}
