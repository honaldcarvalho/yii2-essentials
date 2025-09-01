<?php

use yii\db\Migration;

class m250901_000002_license_soft_limit_and_active_sessions extends Migration
{
    public function safeUp()
    {
        // 1) Alter licenses: heartbeat_seconds + max_users_override
        if ($this->db->schema->getTableSchema('{{%licenses}}', true) !== null) {
            $cols = $this->db->schema->getTableSchema('{{%licenses}}')->columns;
            if (!isset($cols['heartbeat_seconds'])) {
                $this->addColumn('{{%licenses}}', 'heartbeat_seconds', $this->integer()->notNull()->defaultValue(600)->after('license_type_id'));
            }
            if (!isset($cols['max_users_override'])) {
                $this->addColumn('{{%licenses}}', 'max_users_override', $this->integer()->null()->after('heartbeat_seconds'));
            }
        }

        // 2) Create user_active_sessions
        if ($this->db->schema->getTableSchema('{{%user_active_sessions}}', true) === null) {
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

            // Indexes
            $this->createIndex('ux_uas_session', '{{%user_active_sessions}}', ['session_id'], true);
            $this->createIndex('ix_uas_group_active', '{{%user_active_sessions}}', ['group_id', 'is_active']);
            $this->createIndex('ix_uas_user_group', '{{%user_active_sessions}}', ['user_id', 'group_id']);
            $this->createIndex('ix_uas_last_seen', '{{%user_active_sessions}}', ['last_seen_at']);
        }
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%user_active_sessions}}', true) !== null) {
            $this->dropTable('{{%user_active_sessions}}');
        }
        if ($this->db->schema->getTableSchema('{{%licenses}}', true) !== null) {
            $cols = $this->db->schema->getTableSchema('{{%licenses}}')->columns;
            if (isset($cols['max_users_override'])) {
                $this->dropColumn('{{%licenses}}', 'max_users_override');
            }
            if (isset($cols['heartbeat_seconds'])) {
                $this->dropColumn('{{%licenses}}', 'heartbeat_seconds');
            }
        }
    }
}