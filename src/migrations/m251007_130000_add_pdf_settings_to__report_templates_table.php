<?php

use yii\db\Migration;

/**
 * Class m251007_130000_add_pdf_settings_to_report_templates_table
 *
 * Adds PDF rendering configuration fields to `report_templates` table.
 */
class m251007_130000_add_pdf_settings_to__report_templates_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%report_templates}}', 'format', $this->string(10)->notNull()->defaultValue('A4'));
        $this->addColumn('{{%report_templates}}', 'margin_top', $this->integer()->notNull()->defaultValue(40));
        $this->addColumn('{{%report_templates}}', 'margin_bottom', $this->integer()->notNull()->defaultValue(30));
        $this->addColumn('{{%report_templates}}', 'margin_left', $this->integer()->notNull()->defaultValue(0));
        $this->addColumn('{{%report_templates}}', 'margin_right', $this->integer()->notNull()->defaultValue(0));
        $this->addColumn('{{%report_templates}}', 'margin_header', $this->integer()->notNull()->defaultValue(0));
        $this->addColumn('{{%report_templates}}', 'margin_footer', $this->integer()->notNull()->defaultValue(5));
        $this->addColumn('{{%report_templates}}', 'setAutoTopMargin', $this->string(20)->notNull()->defaultValue('stretch'));
        $this->addColumn('{{%report_templates}}', 'setAutoBottomMargin', $this->string(20)->notNull()->defaultValue('pad'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%report_templates}}', 'format');
        $this->dropColumn('{{%report_templates}}', 'margin_top');
        $this->dropColumn('{{%report_templates}}', 'margin_bottom');
        $this->dropColumn('{{%report_templates}}', 'margin_left');
        $this->dropColumn('{{%report_templates}}', 'margin_right');
        $this->dropColumn('{{%report_templates}}', 'margin_header');
        $this->dropColumn('{{%report_templates}}', 'margin_footer');
        $this->dropColumn('{{%report_templates}}', 'setAutoTopMargin');
        $this->dropColumn('{{%report_templates}}', 'setAutoBottomMargin');
    }
}
