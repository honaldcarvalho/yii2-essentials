<?php

use weebz\yii2basics\models\Menu;
use yii\db\Migration;

/**
 * Handles the creation of table `{{%site_sections}}`.
 */
class m240916_191951_create_site_sections_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableName = '{{%site_sections}}';

        if ($this->db->schema->getTableSchema($tableName, true) === null) {
            $this->createTable($tableName, [
                'id' => $this->primaryKey(),
                'name' => $this->string()->notNull(),
                'order' => $this->integer()->defaultValue(0)->notNull(),
                'show_menu' => $this->boolean()->defaultValue(true)->notNull(),
                'status' => $this->boolean()->defaultValue(false)->notNull(),
            ]);
        }

        $this->insert('site_sections', [
            'name' => 'posts',
            'order' => 3,
            'show_menu' => true,
            'status'=>true
        ]);

        $this->insert('site_sections', [
            'name' => 'links',
            'order' => 5,
            'show_menu' => false,
            'status'=>true
        ]);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%site_sections}}');
    }
}
