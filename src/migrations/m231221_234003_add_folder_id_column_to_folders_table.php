<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%folders}}`.
 */
class m231221_234003_add_folder_id_column_to_folders_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%folders}}', 'folder_id', $this->integer()->defaultValue(null));
        $this->addForeignKey(
            'fk-folders-folder_id',
            'folders',
            'folder_id',
            'folders',
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
            'fk-folder-folder_id',
            'folders',
        );
        $this->dropColumn('{{%folders}}', 'folder_id');
    }
}
