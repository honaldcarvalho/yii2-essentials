<?php
namespace croacworks\essentials\helpers;

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * MpdfHelper
 * ----------
 * Converts arbitrary HTML into a version safe and compatible with mPDF rendering.
 *
 * - Cleans <script>, <meta>, <style>, <link>
 * - Converts relative <img src> paths to absolute URLs
 * - Inlines basic Bootstrap-like styling
 * - Optionally injects a base CSS for mPDF rendering
 *
 * Usage:
 *   $cleanHtml = MpdfHelper::normalizeHtml($html, ['injectCss' => true]);
 *   $mpdf->WriteHTML($cleanHtml);
 */
class MpdfHelper
{
    /**
     * Converts an arbitrary HTML string to a format compatible with mPDF.
     *
     * @param string $html     Input HTML
     * @param array  $options  [
     *      'injectCss' => bool Whether to inject a base inline CSS block
     *      'baseUrl'   => string|null  Base URL for resolving relative paths (default: @web)
     * ]
     * @return string
     */
    public static function normalizeHtml(string $html, array $options = []): string
    {
        $injectCss = $options['injectCss'] ?? true;
        $baseUrl   = $options['baseUrl'] ?? Yii::getAlias('@web');

        // 1️⃣ Remove unwanted tags (script, style, link, meta)
        $html = preg_replace('#<(script|style|meta|link)[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#<(script|style|meta|link)[^>]*?>#is', '', $html);

        // 2️⃣ Normalize image sources (convert relative → absolute)
        $html = preg_replace_callback(
            '/<img\s+[^>]*src=["\']([^"\']+)["\'][^>]*>/i',
            function ($matches) use ($baseUrl) {
                $src = $matches[1];
                if (!preg_match('#^https?://#', $src)) {
                    $src = rtrim($baseUrl, '/') . '/' . ltrim($src, '/');
                }
                return str_replace($matches[1], $src, $matches[0]);
            },
            $html
        );

        // 3️⃣ Replace Bootstrap-like classes with inline styles
        $replacements = [
            'text-center' => 'text-align:center;',
            'text-right'  => 'text-align:right;',
            'text-left'   => 'text-align:left;',
            'fw-bold'     => 'font-weight:bold;',
            'fw-semibold' => 'font-weight:600;',
            'fw-light'    => 'font-weight:300;',
            'fst-italic'  => 'font-style:italic;',
            'mt-'         => 'margin-top:%spx;',
            'mb-'         => 'margin-bottom:%spx;',
            'pt-'         => 'padding-top:%spx;',
            'pb-'         => 'padding-bottom:%spx;',
        ];

        foreach ($replacements as $class => $style) {
            if (str_contains($class, '-')) {
                // handle numeric suffixes: e.g., mt-2, mb-3
                $html = preg_replace_callback(
                    '/class=["\'][^"\']*' . preg_quote($class, '/') . '(\d)[^"\']*["\']/',
                    function ($m) use ($class, $style) {
                        $px = (int)$m[1] * 4; // Bootstrap spacing scale (4px per unit)
                        return str_replace($m[0], str_replace('%s', $px, $style), $m[0]);
                    },
                    $html
                );
            } else {
                $html = preg_replace_callback(
                    '/class=["\'][^"\']*' . preg_quote($class, '/') . '[^"\']*["\']/',
                    fn($m) => preg_replace('/class=["\'][^"\']*["\']/', 'style="' . $style . '"', $m[0]),
                    $html
                );
            }
        }

        // 4️⃣ Basic cleanup: remove extra spaces, empty attributes
        $html = preg_replace('/\s{2,}/', ' ', $html);
        $html = preg_replace('/<(\w+)\s+>/i', '<$1>', $html);

        // 5️⃣ Optionally inject base CSS
        if ($injectCss) {
            $css = self::baseCss();
            $html = "<style>{$css}</style>\n" . $html;
        }

        return trim($html);
    }

    /**
     * Returns base CSS rules for mPDF.
     * You can customize it as needed for better print layout.
     */
    protected static function baseCss(): string
    {
        return <<<CSS
            body { font-family: DejaVu Sans, sans-serif; font-size: 12pt; color: #000; }
            h1, h2, h3, h4, h5 { font-weight: bold; margin: 10px 0; }
            h1 { font-size: 22pt; } h2 { font-size: 18pt; } h3 { font-size: 16pt; }
            table { border-collapse: collapse; width: 100%; margin-top: 10px; }
            table, th, td { border: 1px solid #ccc; }
            th, td { padding: 6px 8px; vertical-align: middle; }
            img { max-width: 100%; height: auto; }
            p { margin: 6px 0; line-height: 1.4; }
            .no-border td, .no-border th { border: none !important; }
        CSS;
    }
}
