<?php

use yii\db\Migration;

class m250911_170000_add_slug_to_page_sections extends Migration
{
    public function safeUp()
    {
        // Torna section_id NULL
        $this->alterColumn('{{%pages}}', 'section_id', $this->integer()->null());

        // Recria o índice único para (slug, group_id, language_id, section_id)
        // (precisa dropar o anterior, se existir)
        try { $this->dropIndex('ux_pages_slug_grp_lang_sec', '{{%pages}}'); } catch (\Throwable $e) {}
        $this->createIndex(
            'ux_pages_slug_grp_lang_sec',
            '{{%pages}}',
            ['slug', 'group_id', 'language_id', 'section_id'],
            true
        );
    }   

    public function safeDown()
    {
        // Volta para NOT NULL (se quiser reverter)
        $this->alterColumn('{{%pages}}', 'section_id', $this->integer()->notNull()->defaultValue(1));

        try { $this->dropIndex('ux_pages_slug_grp_lang_sec', '{{%pages}}'); } catch (\Throwable $e) {}
        $this->createIndex(
            'ux_pages_slug_grp_lang_sec',
            '{{%pages}}',
            ['slug', 'group_id', 'language_id', 'section_id'],
            true
        );
    }
}