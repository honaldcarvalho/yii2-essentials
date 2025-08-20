<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%menus}}`.
 */
class m230911_192842_create_meta_tags_table extends Migration
{
    /**
     * {@inheritdoc}
     */

    public function safeUp()
    {
        //$sql = file_get_contents(__DIR__ . '/query_menus_insert.sql');

        $this->createTable('{{%meta_tags}}', [
            'id' => $this->primaryKey(),
            'configuration_id' => $this->integer()->notNull(),
            'name' => $this->string()->notNull(),
            'description' => $this->string(),
            'content' => $this->text()->notNull(),
            'status' => $this->boolean()->defaultValue(true)
        ]);

        $this->insert('meta_tags', [
            'configuration_id' => 1,
            'name' => 'meta_viewport',
            'content'  => 'width=device-width, initial-scale=1, shrink-to-fit=no',
        ]);

        $this->insert('meta_tags', [
            'configuration_id' => 1,
            'name' => 'meta_author',
            'content'  => 'Weebz',
        ]);

        $this->insert('meta_tags', [
            'configuration_id' => 1,
            'name' => 'meta_robots',
            'content'  => 'noindex,nofollow',
        ]);

        $this->insert('meta_tags', [
            'configuration_id' => 1,
            'name' => 'meta_googlebot',
            'content'  => 'noindex,nofollow',
        ]);

        $this->insert('meta_tags', [
            'configuration_id' => 1,
            'name' => 'canonical',
            'content'  => 'weebz.com.br',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%meta_tags}}');
    }
}
