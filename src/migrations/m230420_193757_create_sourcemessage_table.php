<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%source_message}}`.
 */
class m230420_193757_create_sourcemessage_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $sql = file_get_contents(__DIR__ . '/query_source_messages_insert.sql');
        $this->createTable('{{%source_message}}', [
            'id' => $this->primaryKey(),
            'category' => $this->string()->defaultValue('*'),
            'message' => $this->text(),
        ]);
        $this->execute($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%source_message}}');
    }
}
