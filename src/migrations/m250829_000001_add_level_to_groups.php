<?php

use yii\db\Migration;

class m250829_000001_add_level_to_groups extends Migration
{
    public function safeUp()
    {
        // ENUM direto (MariaDB/MySQL)
        $this->execute("ALTER TABLE {{%groups}} 
            ADD COLUMN `level` ENUM('master','admin','user','free') NOT NULL DEFAULT 'user' AFTER `name`");
        // Índice para consultas por nível
        $this->createIndex('idx_groups_level', '{{%groups}}', 'level');
    }

    public function safeDown()
    {
        $this->dropIndex('idx_groups_level', '{{%groups}}');
        $this->dropColumn('{{%groups}}', 'level');
    }
}
