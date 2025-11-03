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

        $this->addColumn('{{%pages}}', 'model_group_id', $this->integer()->null()->after('id'));
        $this->createIndex('idx-pages-model_group_id', '{{%pages}}', 'model_group_id');
        // Backfill
        $this->execute('UPDATE ' . '{{%pages}}' . ' SET model_group_id = id WHERE model_group_id IS NULL');

        $this->addColumn('{{%pages}}', 'list', $this->integer()->null()->after('id'));
        $this->createIndex('idx-pages-list', '{{%pages}}', 'list');
    }

    public function safeDown()
    {

        $this->dropIndexIfExists('idx-pages-model_group_id', '{{%pages}}');
        $this->dropColumn('{{%pages}}', 'model_group_id');

        $this->dropIndexIfExists('idx-pages-list', '{{%pages}}', 'list');
        $this->dropColumn('{{%pages}}', 'list');
    }
}
