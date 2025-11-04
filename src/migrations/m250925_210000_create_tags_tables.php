<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%tags}}` and `{{%post_tags}}`.
 */
class m250925_210000_create_tags_tables extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        // === Tabela de tags ===
        $this->createTable('{{%tags}}', [
            'id' => $this->bigPrimaryKey(),
            'name' => $this->string(100)->notNull()->unique(),
            'slug' => $this->string(120)->notNull()->unique(),
            'status' => $this->smallInteger()->notNull()->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-post_tags-tag_id', '{{%post_tags}}');
        $this->dropTable('{{%tags}}');
    }
}
