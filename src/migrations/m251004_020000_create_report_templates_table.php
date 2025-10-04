<?php

use yii\db\Migration;

/**
 * Handles the creation of table `report_templates`.
 */
class m251004_020000_create_report_templates_table extends Migration
{
    public function safeUp()
    {

        $css = <<< CSS
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            color: #212529;
        }
        h1, h2, h3 {
            font-weight: 600;
            margin: 0.5rem 0;
        }
        h2 {
            font-size: 16pt;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 6px 8px;
            vertical-align: middle;
        }
        thead th {
            background-color:#287c36;
            color: #fff;
            text-align: center;
            font-weight: 600;
        }
        tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .text-center { text-align: center; }
        .text-end   { text-align: right; }
        .fw-bold    { font-weight: bold; }
        .small      { font-size: 8pt; }
        CSS;

        $header = <<< HTML
            <div style="width: 100%; text-align: center; font-family: DejaVu Sans, sans-serif; font-size: 10pt; border-bottom: 1px solid #287c36; padding-bottom: 5px;">
            <table width="100%" style="border-collapse: collapse; height: 65.6562px; width: 100%; border-width: 0px;" border="1">
            <tbody>
            <tr style="height: 65.6562px;">
            <td width="20%" style="text-align: left; height: 65.6562px; border-width: 0px;"><img src="https://croacworks.com.br/images/croacworks-logo-hq.png" alt="CroacWorks Logo" height="60"></td>
            <td width="60%" style="text-align: center; height: 65.6562px; border-width: 0px;">
            <div style="font-size: 14pt; font-weight: bold; color: #287c36;">CroacWorks</div>
            <div style="font-size: 10pt; color: #666;">Saltando da ideia ao resultado com estilo e inovação.</div>
            <div style="font-size: 9pt; color: #666;">CNPJ 07.481.906/0003-14</div>
            </td>
            <td width="20%" style="text-align: right; font-size: 9pt; color: rgb(102, 102, 102); height: 65.6562px; border-width: 0px;">Data: {{date}}<br>Hora: {{time}}</td>
            </tr>
            </tbody>
            </table>
            </div>
        HTML;

        $body = <<< HTML
            <h1 style="text-align: center; color: #287c36;">Financial Report</h1>
            <p><strong>Period:</strong> {date_start} - {date_end}</p>
            <table border="1" width="100%" cellspacing="0" cellpadding="6">
            <thead style="background: #f5f5f5;">
            <tr>
            <th>Patient</th>
            <th>Service</th>
            <th>Date</th>
            <th style="text-align: right;">Value</th>
            </tr>
            </thead>
            <tbody>
            <tr data-each="items">
            <td>{patient_name}</td>
            <td>{service_name}</td>
            <td>{date}</td>
            <td style="text-align: right;">{value}</td>
            </tr>
            </tbody>
            </table>
            <p style="text-align: right; margin-top: 20px;"><strong>Total:</strong> {total}</p>
        HTML;

        $footer = <<< HTML
            <div style="width: 100%; border-top: 1px solid #287c36; padding-top: 5px; font-family: DejaVu Sans, sans-serif; font-size: 8pt; text-align: center; color: #666;"><strong>CroacWorks - </strong>Relatório gerado em {{date}} às {{time}} — Página {PAGENO} de {nbpg}</div>
        HTML;

        $this->createTable('{{%report_templates}}', [
            'id' => $this->primaryKey(),
            'group_id' => $this->integer()->null()->comment('Tenant/Group'),
            'name' => $this->string(255)->notNull()->comment('Template name'),
            'description' => $this->text()->null()->comment('Optional description'),
            'header_html' => $this->text()->defaultValue($header)->null()->comment('Custom header (HTML)'),
            'body_html' => $this->text()->defaultValue($body)->notNull()->comment('Main body (HTML with placeholders)'),
            'footer_html' => $this->text()->defaultValue($footer)->null()->comment('Custom footer (HTML)'),
            'style' => $this->text()->notNull()->defaultValue($css)->comment('Main body (HTML with placeholders)'),
            'status' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx_report_templates_group', '{{%report_templates}}', 'group_id');
        $this->createIndex('idx_report_templates_status', '{{%report_templates}}', 'status');
    }

    public function safeDown()
    {
        $this->dropTable('{{%report_templates}}');
    }
}
