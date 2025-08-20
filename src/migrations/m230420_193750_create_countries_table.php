<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%group}}`.
 */
class m230420_193750_create_countries_table extends Migration
{
    /**
     * {@inheritdoc}
     */

    public function safeUp()
    {
        $sql = file_get_contents(__DIR__ . '/query_countries_insert.sql');

        $this->createTable('{{%countries}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(60)->notNull(),
            'name_pt' => $this->string(60)->notNull(),
            'abbreviation' => $this->string(2),
            'bacen' => $this->integer(5),
            'status' => $this->boolean()->defaultValue(false)
        ]);
        $this->execute($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%countries}}');
    }
}
