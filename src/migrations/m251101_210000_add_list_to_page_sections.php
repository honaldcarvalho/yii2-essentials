<?php

use yii\db\Migration;

/**
 * m251101_210000_add_model_group_id_to_pages
 * ------------------------------------
 */
class m251101_210000_add_model_group_id_to_pages extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('{{%pages}}', true) !== null) {
            if (!$this->hasColumn('{{%page_sections}}', 'list')) {
                $this->addColumn('{{%page_sections}}', 'list', $this->boolean()->defaultValue(true)->after('uri'));
                $this->createIndex('idx-page_sections-list', '{{%page_sections}}', 'list');
            }
        }
    }

    public function safeDown()
    {
        if (!$this->hasColumn('{{%page_sections}}', 'list')) {
            $this->dropIndexIfExists('idx-page_sections-list', '{{%page_sections}}', 'list');
            $this->dropColumn('{{%page_sections}}', 'list', $this->boolean()->defaultValue(true)->after('uri'));
        }
    }
}
