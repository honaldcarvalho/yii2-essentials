<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%files}}`.
 */
class m231221_150221_add_group_id_column_to_files_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%files}}', 'group_id', $this->integer()->defaultValue(1));
        $this->addForeignKey(
            'fk-files-group_id',
            'files',
            'group_id',
            'groups',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey(
            'fk-file-group',
            'files',
        );        
        $this->dropColumn('{{%files}}', 'group_id');
    }
}
