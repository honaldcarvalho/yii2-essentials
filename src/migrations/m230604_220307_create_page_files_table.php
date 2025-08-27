<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%page_files}}`.
 */
class m230604_220307_create_page_files_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%page_files}}', [
            'id' => $this->primaryKey(),
            'page_id' => $this->integer()->notNull(),
            'file_id' => $this->integer()->notNull()
        ]);

        $this->addForeignKey(
            'fk-page_files-file_id',
            'page_files',
            'file_id',
            'files',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-page_files-page_id',
            'page_files',
            'page_id',
            'pages',
            'id',
            'CASCADE'
        );
    }
    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%page_files}}');
    }
}
