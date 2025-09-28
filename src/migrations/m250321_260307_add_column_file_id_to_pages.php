<?php

use yii\db\Migration;

class m250321_260307_add_column_file_id_to_pages extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%pages}}', 'file_id', $this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        
        $this->dropColumn('{{%pages}}', 'file_id');
    }
}
