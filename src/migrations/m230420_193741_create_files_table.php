<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%files}}`.
 */
class m230420_193741_create_files_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%files}}', [
            'id' => $this->primaryKey(),
            'folder_id' => $this->integer(),
            'caption' => $this->integer()->defaultValue(0),
            'name' => $this->string(300)->notNull(),
            'type' => $this->string()->notNull(),
            'description' => $this->string(),
            'path' => $this->string(300)->notNull(),
            'url' => $this->string(300)->notNull(),
            'pathThumb' => $this->string(300),
            'urlThumb' => $this->string(300),
            'extension' => $this->string(6)->notNull(),
            'duration'=> $this->integer()->defaultValue(null),
            'size' => $this->integer()->notNull(),
            'created_at' => $this->dateTime()->defaultValue(new \yii\db\Expression('NOW()')),
            'updated_at' => $this->timestamp()->defaultValue(null)->append('ON UPDATE CURRENT_TIMESTAMP')
        ]);
        
        $this->addForeignKey(
            'fk-files-folder_id',
            'files',
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
        $this->dropTable('{{%files}}');
    }
}
