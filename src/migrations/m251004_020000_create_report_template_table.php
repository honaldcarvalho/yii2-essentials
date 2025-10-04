<?php

use yii\db\Migration;

/**
 * Handles the creation of table `report_template`.
 */
class m251004_020000_create_report_template_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%report_template}}', [
            'id' => $this->primaryKey(),
            'group_id' => $this->integer()->null()->comment('Tenant/Group'),
            'name' => $this->string(255)->notNull()->comment('Template name'),
            'description' => $this->text()->null()->comment('Optional description'),
            'header_html' => $this->text()->null()->comment('Custom header (HTML)'),
            'footer_html' => $this->text()->null()->comment('Custom footer (HTML)'),
            'body_html' => $this->text()->notNull()->comment('Main body (HTML with placeholders)'),
            'status' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx_report_template_group', '{{%report_template}}', 'group_id');
        $this->createIndex('idx_report_template_status', '{{%report_template}}', 'status');
    }

    public function safeDown()
    {
        $this->dropTable('{{%report_template}}');
    }
}
