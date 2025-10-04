<?php

namespace croacworks\essentials\helpers;

use croacworks\essentials\models\ReportTemplate;
use Mpdf\Mpdf;
use yii\web\NotFoundHttpException;

/**
 * ReportTemplateHelper
 * --------------------
 * 
 * A rendering helper for dynamic HTML report templates.
 *
 * This helper processes stored templates and replaces placeholders
 * and loop sections (lists) with real data. It supports three loop syntaxes:
 *
 *  1. Classic Mustache-style:
 *     {{#each items}} ... {{/each}}
 *
 *  2. Comment-based (safe inside tables for TinyMCE):
 *     <!-- {{#each items}} --> ... <!-- {{/each}} -->
 *
 *  3. Attribute-based:
 *     <tr data-each="items"> ... </tr>
 *
 * Each loop will repeat the inner block for every element in `$data['items']`.
 *
 * Simple placeholders like `{patient_name}` or `{total}` are also replaced.
 *
 * ## Example Template
 * ```html
 * <h2>Financial Report</h2>
 * <table border="1" width="100%">
 *   <thead>
 *     <tr><th>Patient</th><th>Service</th><th>Date</th><th>Value</th></tr>
 *   </thead>
 *   <tbody>
 *     <!-- {{#each items}} -->
 *     <tr>
 *       <td>{patient_name}</td>
 *       <td>{service_name}</td>
 *       <td>{date}</td>
 *       <td style="text-align:right">{value}</td>
 *     </tr>
 *     <!-- {{/each}} -->
 *   </tbody>
 * </table>
 * <p style="text-align:right"><b>Total:</b> {total}</p>
 * ```
 *
 * ## Example Data
 * ```php
 * $data = [
 *     'total' => '$1,250.00',
 *     'items' => [
 *         ['patient_name' => 'Alice', 'service_name' => 'Consultation', 'date' => '2025-10-01', 'value' => '$200.00'],
 *         ['patient_name' => 'Bob',   'service_name' => 'X-Ray',        'date' => '2025-10-02', 'value' => '$400.00'],
 *     ]
 * ];
 * 
 * echo ReportTemplateHelper::render($templateHtml, $data);
 * ```
 */
class ReportTemplateHelper
{
    /**
     * Render a report template with dynamic data.
     *
     * Replaces:
     *  - Simple placeholders: {key}
     *  - Loops: {{#each key}} ... {{/each}}
     *  - Comment-based loops: <!-- {{#each key}} --> ... <!-- {{/each}} -->
     *  - Attribute loops: <tag data-each="key"> ... </tag>
     *
     * @param string $template HTML template string.
     * @param array $data      Data array for placeholders and loops.
     * @return string          Rendered HTML.
     */
    public static function render(string $template, array $data): string
    {
        // Step 1: Process loops that use the data-each attribute
        $template = self::renderDataEachAttribute($template, $data);

        // Step 2: Process comment-based loops (safe for TinyMCE)
        $template = self::renderCommentLoops($template, $data);

        // Step 3: Process classic {{#each}} ... {{/each}} loops
        $template = self::renderClassicLoops($template, $data);

        // Step 4: Replace simple placeholders {key}
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $template = str_replace('{' . $key . '}', (string)$value, $template);
            }
        }

        return $template;
    }

    /**
     * Render loops defined as: {{#each key}} ... {{/each}}
     *
     * @param string $tpl  HTML template
     * @param array  $data Data source
     * @return string
     */
    protected static function renderClassicLoops(string $tpl, array $data): string
    {
        if (preg_match_all('/\{\{#each\s+([A-Za-z0-9_\-]+)\s*\}\}([\s\S]*?)\{\{\/each\}\}/', $tpl, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $key   = $m[1];
                $block = $m[2];
                $out   = self::repeatBlock($block, $data[$key] ?? []);
                $tpl   = str_replace($m[0], $out, $tpl);
            }
        }
        return $tpl;
    }

    /**
     * Render loops defined as HTML comments:
     * <!-- {{#each key}} --> ... <!-- {{/each}} -->
     *
     * @param string $tpl  HTML template
     * @param array  $data Data source
     * @return string
     */
    protected static function renderCommentLoops(string $tpl, array $data): string
    {
        if (preg_match_all('/<!--\s*\{\{#each\s+([A-Za-z0-9_\-]+)\s*\}\}\s*-->([\s\S]*?)<!--\s*\{\{\/each\}\}\s*-->/', $tpl, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $key   = $m[1];
                $block = $m[2];
                $out   = self::repeatBlock($block, $data[$key] ?? []);
                $tpl   = str_replace($m[0], $out, $tpl);
            }
        }
        return $tpl;
    }

    /**
     * Render loops defined as: <tag data-each="key"> ... </tag>
     *
     * @param string $tpl  HTML template
     * @param array  $data Data source
     * @return string
     */
    protected static function renderDataEachAttribute(string $tpl, array $data): string
    {
        // Matches any element with a "data-each" attribute
        if (preg_match_all('/<([a-zA-Z0-9:\-]+)([^>]*)\sdata-each="([A-Za-z0-9_\-]+)"([^>]*)>([\s\S]*?)<\/\1>/', $tpl, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $tag        = $m[1];
                $beforeAttr = $m[2];
                $key        = $m[3];
                $afterAttr  = $m[4];
                $inner      = $m[5];

                $items = $data[$key] ?? [];
                $out   = '';

                if (is_array($items)) {
                    foreach ($items as $row) {
                        $rowHtml = $inner;
                        foreach ($row as $col => $val) {
                            $rowHtml = str_replace('{' . $col . '}', (string)$val, $rowHtml);
                        }
                        $out .= "<{$tag}{$beforeAttr}{$afterAttr}>{$rowHtml}</{$tag}>";
                    }
                }

                $tpl = str_replace($m[0], $out, $tpl);
            }
        }
        return $tpl;
    }

    /**
     * Helper: repeat a block for each row in a dataset.
     *
     * @param string $block Inner HTML of the loop.
     * @param array  $rows  Data array for iteration.
     * @return string
     */
    protected static function repeatBlock(string $block, array $rows): string
    {
        $rendered = '';

        if (!is_array($rows)) {
            return $rendered;
        }

        foreach ($rows as $row) {
            $rowHtml = $block;
            if (is_array($row)) {
                foreach ($row as $col => $val) {
                    $rowHtml = str_replace('{' . $col . '}', (string)$val, $rowHtml);
                }
            }
            $rendered .= $rowHtml;
        }

        return $rendered;
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
        return \croacworks\essentials\helpers\ReportTemplateHelper::generatePdf(
            [
                'templateId' => $model->id,
                'data' => $sampleData,
                'filename' => 'Report',
                'mode' => 'inline',
                'config' => [
                    'format'        => 'A4',
                    'margin_top'    => 40,
                    'margin_bottom' => 30,
                    'margin_left'   => 15,
                    'margin_right'  => 15,
                ],
                'normalizeHtml' => Yii::$app->request->get('normalize') ?? false,
                
            ]
        );
     *
     * @param array  $defaults
     * @return mixed
     * @throws NotFoundHttpException
     */
    public static function generatePdf(
        array $defaults = [
            'templateId' => null,
            'data' => null,
            'filename' => 'Report',
            'mode' => 'inline',
            'custom_body' => null,
            'config' => [
                'format'        => 'A4',
                'margin_top'    => 40,
                'margin_bottom' => 30,
                'margin_left'   => 15,
                'margin_right'  => 15,
            ],
            'normalizeHtml' => false
        ]
    ) {

        $template = ReportTemplate::findOne($defaults['templateId']);

        if (!$template) {
            throw new NotFoundHttpException("Template not found");
        }

        if(!$defaults['custom_body'])
            $html = self::render($template->body_html, $defaults['data']);
        else
            $html = self::render($defaults['custom_body'], $defaults['data']);

        $mpdf = new \Mpdf\Mpdf($defaults['config']);
        $replacements = [
            '{{date}}' => date('d/m/Y'),
            '{{time}}' => date('H:i'),
        ];

        if ($template->header_html) {
            $header = strtr($template->header_html, $replacements);
            $mpdf->SetHTMLHeader($header);
        }

        if ($template->footer_html) {
            $footer = strtr($template->footer_html, $replacements);
            $mpdf->SetHTMLFooter($footer);
        }

        if ($template->style) {
            $mpdf->WriteHTML($template->style, \Mpdf\HTMLParserMode::HEADER_CSS);
        }

        if($defaults['normalizeHtml']){
            $html = MpdfHelper::normalizeHtml($html);
        }

        $mpdf->WriteHTML($html);

        $filename = $defaults['filename'] . '.pdf';
        $dest = ($defaults['mode'] === 'download')
            ? \Mpdf\Output\Destination::DOWNLOAD
            : \Mpdf\Output\Destination::INLINE;

        return $mpdf->Output($filename, $dest);
    }
}
