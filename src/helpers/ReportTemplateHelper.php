<?php

namespace croacworks\essentials\helpers;

use croacworks\essentials\models\ReportTemplate;
use Mpdf\Mpdf;
use Yii;
use yii\helpers\StringHelper;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
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


    /**
     * Default replacements for any PDF/Report template.
     * These values can be used in any {{placeholder}} within header, body, or footer.
     *
     * Example:
     *   {{date}} → 04/10/2025
     *   {{user_name}} → Honald Carvalho
     *   {{company_name}} → CroacWorks Tecnologia LTDA
     *   {{page}} / {{pages}} → Page counters
     */
    public static function defaultReplacements(): array
    {
        $user     = Yii::$app->user->identity ?? null;
        $request  = Yii::$app->request ?? null;
        $params   = Yii::$app->params ?? [];

        // Basic info
        $ip       = $request?->userIP ?? '0.0.0.0';
        $hostname = $request?->hostName ?? gethostname();
        $uuid     = strtoupper(StringHelper::basename(Yii::$app->security->generateRandomString(10)));

        // Company info (fallbacks)
        $company = [
            'name'    => $params['company.name']    ?? 'CroacWorks Tecnologia LTDA',
            'representant'    => $params['company.representant']    ?? 'Honald Carvalho da Silva',
            'cnpj'    => $params['company.cnpj']    ?? '60.027.572/0001-96',
            'address' => $params['company.address'] ?? 'Rua 19 de Maio, nº 906 - Teresina/PI',
            'email'   => $params['company.email']   ?? 'contato@croacworks.com.br',
            'phone'   => $params['company.phone']   ?? '(86) 4002-8922',
            'site'    => $params['company.site']    ?? 'https://croacworks.com.br',
            'logo'    => $params['company.logo']    ?? 'https://croacworks.com.br/images/croacworks-logo-hq.png',
        ];

        // Timezone
        $tz = new \DateTimeZone(date_default_timezone_get());
        $offsetHours = $tz->getOffset(new \DateTime()) / 3600;
        $offset = sprintf("%+03d:00", $offsetHours);

        // Unique token for QR/validation
        $hash = strtoupper(substr(md5(uniqid('', true)), 0, 10));

        // 🔹 Generate QR Code (inline base64)
        try {
            $qrText = $params['company.validation_url']
                ?? (Yii::$app->request->hostInfo . Yii::$app->request->url);

            $qrPayload = "{$qrText}?hash={$hash}&uuid={$uuid}";

            $options = new QROptions([
                'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                'eccLevel'   => QRCode::ECC_L,
                'scale'      => 3,
                'imageBase64' => true,
            ]);

            $qr_code = (new QRCode($options))->render($qrPayload);
        } catch (\Throwable $e) {
            $qr_code = ''; // fallback silencioso se não conseguir gerar
        }

        return [
            // 🗓️ Date & Time
            '{{date}}'         => date('d/m/Y'),
            '{{time}}'         => date('H:i'),
            '{{datetime}}'     => date('d/m/Y H:i'),
            '{{date_extend}}'  => Yii::$app->formatter->asDate('now', 'php:j \d\e F \d\e Y'),
            '{{weekday}}'      => Yii::$app->formatter->asDate('now', 'php:l'),
            '{{month_name}}'   => Yii::$app->formatter->asDate('now', 'php:F'),
            '{{year}}'         => date('Y'),

            // 🌐 System / User
            '{{ip}}'           => $ip,
            '{{hostname}}'     => $hostname,
            '{{user_name}}'    => $user->name ?? Yii::t('app', 'User is guest'),
            '{{user_email}}'   => $user->email ?? '',
            '{{user_group}}'   => method_exists($user, 'groupName') ? $user->groupName : '',
            '{{generated_by}}' => 'CroacWorks System v3.2',

            // 🏢 Company
            '{{company_name}}'    => $company['name'],
            '{{company_representant}}'    => $company['representant'],
            '{{company_cnpj}}'    => $company['cnpj'],
            '{{company_address}}' => $company['address'],
            '{{company_email}}'   => $company['email'],
            '{{company_phone}}'   => $company['phone'],
            '{{company_site}}'    => $company['site'],
            '{{company_logo}}'    => "<img src=\"{$company['logo']}\" height=\"40\">",

            // 📄 Document & Page
            '{{page}}'        => '{PAGENO}',
            '{{pages}}'       => '{nbpg}',
            '{{page_info}}'   => 'Página {PAGENO} de {nbpg}',
            '{{file_name}}'   => Yii::$app->controller->id . '-' . date('YmdHis') . '.pdf',
            '{{report_title}}' => Yii::$app->view->title ?? 'Relatório',
            '{{signature_line}}' => '_________________________',
            '{{qr_code}}'     => "<img src=\"{$qr_code}\" width=\"80\" height=\"80\" alt=\"QR Code\">",

            // 🔒 Advanced info
            '{{uuid}}'        => $uuid,
            '{{hash}}'        => $hash,
            '{{timestamp}}'   => time(),
            '{{printed_at}}'  => date('d/m/Y H:i:s'),
            '{{timezone}}'    => date_default_timezone_get() . " ({$offset})",
            '{{runtime_env}}' => YII_ENV,
        ];
    }

    public static function render(string $template, array $data = [], bool $applyDefaults = true): string
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

        // 🔹 Aplica marcações globais se solicitado
        if ($applyDefaults) {
            $template = strtr($template, self::defaultReplacements());
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
    public static function generatePdf(array $options = [])
    {
        // tempDir gravável
        $tempDir = Yii::getAlias('@runtime/mpdf');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }

        // Defaults
        $defaults = [
            'templateId'   => null,
            'data'         => [],            // ⚠️ agora padronizado como array
            'filename'     => 'Report',
            'mode'         => 'inline',
            'custom_body'  => null,          // HTML do documento (content) a ser injetado em {content}
            'config'       => [
                'format'             => 'A4',
                'margin_top'         => 40,
                'margin_bottom'      => 30,
                'margin_left'        => 0,
                'margin_right'       => 0,
                'margin_header'      => 0,
                'margin_footer'      => 5,
                'setAutoTopMargin'   => 'stretch',
                'setAutoBottomMargin' => 'pad',
                'tempDir'            => $tempDir,
            ],
            'normalizeHtml' => false,
        ];

        // Merge
        $params = array_replace_recursive($defaults, $options);

        // Garante array
        $data = is_array($params['data']) ? $params['data'] : [];

        // Carrega template
        $template = ReportTemplate::findOne($params['templateId']);
        if (!$template) {
            throw new NotFoundHttpException('Template not found');
        }

        // =============================
        // MERGE EM 2 ETAPAS (sem hardcode)
        // =============================

        // 1) Renderiza o conteúdo do documento (custom_body) com $data
        $renderedContent = '';
        if (!empty($params['custom_body'])) {
            $renderedContent = self::render($params['custom_body'], $data, true);
        }

        // 2) Injeta {content} no body_html do template
        $baseBody = (string)$template->body_html;
        if ($renderedContent !== '') {
            $baseBody = str_replace('{content}', $renderedContent, $baseBody);
        }

        // 3) Renderiza o body completo do template (placeholders restantes + loops)
        $html = self::render($baseBody, $data, true);

        // Instancia mPDF
        $mpdf = new Mpdf($params['config']);

        // Header
        if (!empty($template->header_html)) {
            $header = self::render($template->header_html, $data, true);
            $mpdf->SetHTMLHeader($header);
        }

        // Footer
        if (!empty($template->footer_html)) {
            $footer = self::render($template->footer_html, $data, true);
            $mpdf->SetHTMLFooter($footer);
        }

        // CSS
        if (!empty($template->style)) {
            $mpdf->WriteHTML($template->style, \Mpdf\HTMLParserMode::HEADER_CSS);
        }

        // Normalização opcional
        if (!empty($params['normalizeHtml'])) {
            $html = MpdfHelper::normalizeHtml($html);
        }

        // Body final
        $mpdf->WriteHTML($html);

        // Output
        $filename = $params['filename'] . '.pdf';
        $dest = ($params['mode'] === 'download')
            ? \Mpdf\Output\Destination::DOWNLOAD
            : \Mpdf\Output\Destination::INLINE;

        return $mpdf->Output($filename, $dest);
    }
}
