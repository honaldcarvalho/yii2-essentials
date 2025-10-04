<?php

namespace croacworks\essentials\helpers;

use Mpdf\Mpdf;
use yii\web\NotFoundHttpException;
use croacworks\essentials\models\ReportTemplate;

class ReportTemplateHelper
{
    /**
     * Render a template with placeholders and optional loops.
     *
     * Placeholders use the syntax:
     *   - Simple values: {field_name}
     *   - Loops: {{#each items}} ... {subfield} ... {{/each}}
     *
     * Example of a template with simple values:
     *   <h1>Patient Report</h1>
     *   <p>Name: {patient_name}</p>
     *   <p>Date: {date}</p>
     *
     * Example of a template with a list:
     *   <h1>Financial Report</h1>
     *   <p>Period: {date_start} - {date_end}</p>
     *   <table border="1" width="100%">
     *       <tr><th>Service</th><th>Value</th></tr>
     *       {{#each items}}
     *       <tr>
     *           <td>{service_name}</td>
     *           <td>{value}</td>
     *       </tr>
     *       {{/each}}
     *   </table>
     *   <p><b>Total:</b> {total}</p>
     *
     * Example of data for a single report:
     *   $data = [
     *       'patient_name' => 'John Doe',
     *       'date' => '2025-10-04'
     *   ];
     *
     * Example of data for a list report:
     *   $data = [
     *       'date_start' => '2025-10-01',
     *       'date_end'   => '2025-10-04',
     *       'total'      => '$500.00',
     *       'items'      => [
     *           ['service_name' => 'X-Ray', 'value' => '$200.00'],
     *           ['service_name' => 'Consultation', 'value' => '$300.00'],
     *       ],
     *   ];
     *
     * @param string $template HTML template with placeholders
     * @param array  $data     Data array to inject into placeholders
     * @return string          Final rendered HTML
     */
    public static function render(string $template, array $data): string
    {
        // Replace simple placeholders {key}
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $template = str_replace("{" . $key . "}", (string)$value, $template);
            }
        }

        // Process loops {{#each items}} ... {{/each}}
        if (preg_match_all('/\{\{#each (.*?)\}\}([\s\S]*?)\{\{\/each\}\}/', $template, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $arrayKey = $match[1];
                $block    = $match[2];
                $rendered = '';

                if (isset($data[$arrayKey]) && is_array($data[$arrayKey])) {
                    foreach ($data[$arrayKey] as $row) {
                        $rowHtml = $block;
                        foreach ($row as $col => $val) {
                            $rowHtml = str_replace("{" . $col . "}", (string)$val, $rowHtml);
                        }
                        $rendered .= $rowHtml;
                    }
                }

                $template = str_replace($match[0], $rendered, $template);
            }
        }

        return $template;
    }

    /**
     * Render and generate a PDF file using an existing ReportTemplate from DB.
     *
     * The template is loaded from the "report_template" table and can define:
     *   - header_html (PDF header, optional)
     *   - footer_html (PDF footer, optional)
     *   - body_html   (main template with placeholders)
     *
     * Example:
     *   return ReportTemplateHelper::generatePdf(
     *       $templateId,
     *       [
     *           'patient_name' => 'John Doe',
     *           'date' => '2025-10-04'
     *       ],
     *       'Patient_Report'
     *   );
     *
     * @param int    $templateId Template ID from DB
     * @param array  $data       Data array for placeholders
     * @param string $filename   Output filename (without extension)
     * @param string $mode       Output mode: "inline" (default) or "download"
     * @return mixed
     * @throws NotFoundHttpException
     */
    public static function generatePdf(int $templateId, array $data, string $filename = 'Report', string $mode = 'inline')
    {
        $template = ReportTemplate::findOne($templateId);
        if (!$template) {
            throw new NotFoundHttpException("Template not found");
        }

        $html = self::render($template->body_html, $data);

        $mpdf = new Mpdf(['format' => 'A4']);
        if ($template->header_html) {
            $mpdf->SetHeader($template->header_html);
        }
        if ($template->footer_html) {
            $mpdf->SetFooter($template->footer_html);
        }

        $mpdf->WriteHTML($html);

        $filename = $filename . '.pdf';
        $dest = ($mode === 'download') ? \Mpdf\Output\Destination::DOWNLOAD : \Mpdf\Output\Destination::INLINE;

        return $mpdf->Output($filename, $dest);
    }
}
