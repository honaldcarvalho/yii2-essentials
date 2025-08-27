<?php

use yii\db\Migration;

/**
 * Handles changing the unique slug constraint to a composite key (slug + language_id).
 */
class m250816_000002_remove_pages_slug_to_composite_key extends Migration
{
    public function safeUp()
    {
        $this->dropIndex('idx-pages-slug-language_id-unique', '{{%pages}}');
    }

    public function safeDown()
    {
        $this->createIndex(
            'idx-pages-slug-language_id-unique',
            '{{%pages}}',
            ['slug', 'language_id'],
            true // unique
        );
    }
}
