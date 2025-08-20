<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%message}}`.
 */
class m230420_193757_create_message_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $sql = file_get_contents(__DIR__ . '/query_messages_insert.sql');
        $this->createTable('{{%message}}', [
            'id' => $this->integer()->notNull(),
            'language' => $this->string()->notNull(),
            'translation' => $this->text(),
        ]);
        $this->addPrimaryKey('id-pk','message',['id','language']);
        $this->execute($sql);

    }

    
    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%message}}');
    }
}
