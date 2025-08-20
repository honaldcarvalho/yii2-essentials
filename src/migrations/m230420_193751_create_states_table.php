<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%states}}`.
 */
class m230420_193751_create_states_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $sql = file_get_contents(__DIR__ . '/query_states_insert.sql');

        $this->createTable('{{%states}}', [
            'id' => $this->primaryKey(),
            'country_id' => $this->integer(3),
            'name' => $this->string(250)->notNull(),
            'uf' => $this->string(2),
            'ibge' => $this->integer(7),
            'ddd' => $this->string(),
            'status' => $this->boolean()->defaultValue(true)
        ]);

        $this->addForeignKey(
            'fk-states-country_id',
            'states',
            'country_id',
            'countries',
            'id',
            'CASCADE'
        );

        $this->execute($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%states}}');
    }
}
