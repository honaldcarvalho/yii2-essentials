<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%pages}}`.
 */
class m230520_203121_create_pages_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%pages}}', [
            'id' => $this->primaryKey(),      
            'group_id' => $this->integer(),      
            'language_id' => $this->integer(),
            'page_section_id' => $this->integer(),
            'slug' => $this->string()->notNull()->unique(),
            'title' => $this->string()->notNull(),
            'description' => $this->string(300),
            'content' => $this->text(),
            'custom_css' => $this->text(),
            'custom_js' => $this->text(),
            'keywords' => $this->text(),
            'created_at' => $this->dateTime()->defaultValue(new \yii\db\Expression('NOW()')),
            'updated_at' => $this->timestamp()->defaultValue(null)->append('ON UPDATE CURRENT_TIMESTAMP'),
            'status' => $this->integer()->defaultValue(1),
        ]);

        $this->addForeignKey(
            'fk-pages-page_section_id',
            'pages',
            'section_id',
            'page_sections',
            'id',
            'RESTRICT'
        );

        $this->addForeignKey(
            'fk-pages-group_id',
            'pages',
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
        $this->dropTable('{{%pages}}');
    }
}
