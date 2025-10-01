<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%page_sections}}`.
 */
class m230520_202200_create_page_sections_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%page_sections}}', [
            'id' => $this->primaryKey(),
            'group_id' => $this->integer(),
            'parent_id' => $this->integer()->defaultValue(null),
            'name' => $this->string()->notNull(),
            'uri' => $this->string()->defaultValue('#')->notNull(),
            'status' => $this->integer()->defaultValue(1)
        ]);

        $this->addForeignKey(
            'fk-page_sections-parent_id',
            'page_sections',
            'parent_id',
            'page_sections',
            'id',
            'RESTRICT'
        );

        $this->addForeignKey(
            'fk-page_sections-group_id',
            'page_sections',
            'group_id',
            'groups',
            'id',
            'RESTRICT'
        );
        
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%sections}}');
    }
}
