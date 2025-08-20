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
        $this->createTable('{{%message}}', [
            'id' => $this->integer()->notNull(),
            'language' => $this->string()->notNull(),
            'translation' => $this->text(),
        ]);
        $this->addPrimaryKey('id-pk','message',['id','language']);
    }

    
    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%message}}');
    }
}
