<?php

namespace App\Support;

/**
 * HTML embebido en formularios/vistas Filament con estilos coherentes en modo claro y oscuro.
 *
 * @see public/css/clinica-norte-admin.css — clases cn-html-table*, cn-embedded-*
 */
final class FilamentEmbeddedHtml
{
    /**
     * @param  array<int, string|array{0: string, 1?: 'left'|'right'|'center'}>  $headers
     * @param  array<int, array<int, string>>  $rows  Celdas ya escapadas o seguras
     */
    public static function table(
        array $headers,
        array $rows,
        int $emptyColspan = 1,
        string $emptyMessage = 'Sin filas.',
    ): string {
        $html = '<div class="cn-html-table-wrap"><table class="cn-html-table"><thead><tr>';

        foreach ($headers as $header) {
            $label = is_array($header) ? $header[0] : $header;
            $align = is_array($header) ? ($header[1] ?? 'left') : 'left';
            $alignClass = $align !== 'left' ? ' cn-html-table__cell--'.$align : '';
            $html .= '<th class="'.$alignClass.'">'.e($label).'</th>';
        }

        $html .= '</tr></thead><tbody>';

        if ($rows === []) {
            $html .= '<tr class="cn-html-table__empty"><td colspan="'.$emptyColspan.'">'
                .e($emptyMessage).'</td></tr>';
        } else {
            foreach ($rows as $cells) {
                $html .= '<tr>';
                foreach ($cells as $i => $cell) {
                    $align = 'left';
                    if (isset($headers[$i]) && is_array($headers[$i])) {
                        $align = $headers[$i][1] ?? 'left';
                    }
                    $alignClass = $align !== 'left' ? ' cn-html-table__cell--'.$align : '';
                    $html .= '<td class="'.$alignClass.'">'.$cell.'</td>';
                }
                $html .= '</tr>';
            }
        }

        return $html.'</tbody></table></div>';
    }

    public static function intro(string $text, string|int|null $strong = null, ?string $warnSuffix = null): string
    {
        $html = '<p class="cn-preview-intro">'.$text;
        if ($strong !== null && $strong !== '') {
            $html .= ' <strong>'.e((string) $strong).'</strong>';
        }
        if ($warnSuffix !== null && $warnSuffix !== '') {
            $html .= ' <span class="cn-preview-warn">'.e($warnSuffix).'</span>';
        }

        return $html.'</p>';
    }

    public static function footnote(string $text): string
    {
        return '<p class="cn-preview-footnote">'.e($text).'</p>';
    }

    public static function muted(string $text): string
    {
        return '<p class="cn-embedded-muted">'.e($text).'</p>';
    }

    public static function text(string $html): string
    {
        return '<p class="cn-embedded-text">'.$html.'</p>';
    }
}
