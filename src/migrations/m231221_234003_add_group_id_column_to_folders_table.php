<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%folders}}`.
 */
class m231221_234003_add_group_id_column_to_folders_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%folders}}', 'group_id', $this->integer()->defaultValue(1));
        $this->addForeignKey(
            'fk-folders-group_id',
            'folders',
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
            'fk-folder-group_id',
            'folders',
        );
        $this->dropColumn('{{%folders}}', 'group_id');
    }
}
