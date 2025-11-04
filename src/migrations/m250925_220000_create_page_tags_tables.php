<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%page_tags}}`.
 */
class m250925_220000_create_page_tags_tables extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        // === Tabela intermediária page_tags ===
        $this->createTable('{{%page_tags}}', [
            'page_id' => $this->integer()->notNull(),
            'tag_id'  => $this->integer()->notNull(),
            'PRIMARY KEY(page_id, tag_id)',
        ], $tableOptions);

        $this->createIndex('idx-page_tags-tag_id', '{{%page_tags}}', 'tag_id');

        $this->addForeignKey(
            'fk-page_tags-page_id',
            '{{%page_tags}}',
            'page_id',
            '{{%pages}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-page_tags-tag_id',
            '{{%page_tags}}',
            'tag_id',
            '{{%tags}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-page_tags-tag_id', '{{%page_tags}}');
        $this->dropForeignKey('fk-page_tags-page_id', '{{%page_tags}}');

        $this->dropTable('{{%page_tags}}');
    }
}
