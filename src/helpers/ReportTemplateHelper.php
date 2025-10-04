<?php

namespace croacworks\essentials\helpers;


use Yii;
use croacworks\essentials\models\ReportTemplate;
use Spatie\Browsershot\Browsershot;
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

    use Spatie\Browsershot\Browsershot;
    use yii\web\NotFoundHttpException;
    use Yii;

    public static function generatePdf(
        int $templateId,
        array $data,
        string $filename = 'Report',
        string $mode = 'inline'
    ) {
        $template = ReportTemplate::findOne($templateId);
        if (!$template) {
            throw new NotFoundHttpException("Template not found");
        }

        // Render placeholders no corpo, cabeçalho e rodapé
        $body   = self::render((string)$template->body_html,   $data);
        $header = self::render((string)$template->header_html, $data);
        $footer = self::render((string)$template->footer_html, $data);

        // HTML principal (sem header/footer fixos)
        $html = "
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                body { font-family: DejaVu Sans, sans-serif; font-size: 12px; margin:0; padding:20px; }
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #333; padding: 6px; }
                th { background: #f5f5f5; }
            </style>
        </head>
        <body>
            {$body}
        </body>
        </html>
    ";

        // Configura o Browsershot
        $browsershot = Browsershot::html($html)
            ->format('A4')
            ->showBackground()
            ->margins(60, 20, 60, 20) // top, right, bottom, left
            ->setOption('printBackground', true)
            ->setOption('displayHeaderFooter', true);

        // Cabeçalho (todas as páginas)
        if (trim($header) !== '') {
            $headerTemplate = "
            <div style='font-size:12px; width:100%; text-align:center; font-family:DejaVu Sans, sans-serif;'>
                {$header}
            </div>
        ";
            $browsershot->setOption('headerTemplate', $headerTemplate);
        }

        // Rodapé (todas as páginas, com paginação)
        $footerContent = $footer ?: '';
        $footerTemplate = "
        <div style='font-size:10px; width:100%; text-align:center; font-family:DejaVu Sans, sans-serif;'>
            {$footerContent}
            <span style='float:right;'>Página <span class='pageNumber'></span> de <span class='totalPages'></span></span>
        </div>
    ";
        $browsershot->setOption('footerTemplate', $footerTemplate);

        // Gera o PDF (string binária)
        $output = $browsershot->pdf();

        $filename = $filename . '.pdf';

        if ($mode === 'download') {
            return Yii::$app->response->sendContentAsFile(
                $output,
                $filename,
                ['mimeType' => 'application/pdf']
            );
        }

        // inline (abre no navegador)
        Yii::$app->response->headers->set('Content-Type', 'application/pdf');
        Yii::$app->response->headers->set('Content-Disposition', 'inline; filename="' . $filename . '"');

        return Yii::$app->response->sendContentAsFile($output, $filename, [
            'mimeType' => 'application/pdf',
            'inline'   => true
        ]);
    }
}
