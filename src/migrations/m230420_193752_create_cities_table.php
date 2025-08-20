<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%cities}}`.
 */
class m230420_193752_create_cities_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $sql = file_get_contents(__DIR__ . '/query_cities_insert.sql');

        $this->createTable('{{%cities}}', [
            'id' => $this->primaryKey(),
            'state_id' => $this->integer(2),
            'name' => $this->string(250)->notNull(),
            'ibge' => $this->integer(7),
            'status' => $this->boolean()->defaultValue(true)
        ]);

        $this->addForeignKey(
            'fk-cities-state_id',
            'cities',
            'state_id',
            'states',
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
        $this->dropTable('{{%cities}}');
    }
}
