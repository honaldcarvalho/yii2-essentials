<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%parameters}}`.
 */
class m230911_192841_create_parameters_table extends Migration
{
    /**
     * {@inheritdoc}
     */

    public function safeUp()
    {
        //$sql = file_get_contents(__DIR__ . '/query_menus_insert.sql');

        $this->createTable('{{%parameters}}', [
            'id' => $this->primaryKey(),
            'configuration_id' => $this->integer()->notNull(),
            'description' => $this->string(),
            'name' => $this->string()->notNull(),
            'value' => $this->text()->notNull(),
            'status' => $this->boolean()->defaultValue(true)
        ]);

        $this->insert('{{%parameters}}', [
            'configuration_id' => 1,
            'description' => 'Max upload size',
            'name' => 'max_upload_size',
            'value' => '10',
            'status' => true
        ]);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%parameters}}');
    }
}
