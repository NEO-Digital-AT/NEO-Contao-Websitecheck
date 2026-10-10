<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Design;

use Twig\Environment;

/**
 * Turns a design (theme + blocks) into the HTML of the PDF report or the
 * e-mail: every block renders its Twig partial with the shared report context
 * (result, areas, brand, images, …), the frame template wraps the blocks with
 * the CSS for Dompdf/Chromium or the e-mail table skeleton.
 *
 * toTwig() produces the same document as an editable Twig source for the
 * "advanced" mode: the frame plus one include per block with its properties.
 */
final class DesignRenderer
{
    public function __construct(private readonly Environment $twig)
    {
    }

    /**
     * @param array<string, mixed>|string|null $raw     stored design JSON
     * @param array<string, mixed>             $context ReportRenderer::context() (+ vars at top level)
     */
    public function render(string $kind, array|string|null $raw, array $context, bool $markers = false): string
    {
        $design = Design::prepare($raw, $kind, (array) ($context['vars'] ?? []));
        $context['t'] = $this->theme($design['theme'], $context);
        $context['design'] = $design;
        $html = '';

        foreach ($design['blocks'] as $block) {
            $html .= $this->renderBlock($kind, $block, $context, $markers);
        }

        return $this->twig->render('@Contao/neo_websitecheck/'.$kind.'_frame.html.twig', $context + ['content' => $html]);
    }

    /**
     * @param array{id: string, type: string, props: array<string, mixed>} $block
     * @param array<string, mixed>                                          $context
     */
    public function renderBlock(string $kind, array $block, array $context, bool $markers = false): string
    {
        $inner = $this->twig->render(
            '@Contao/neo_websitecheck/blocks/'.$kind.'/'.$block['type'].'.html.twig',
            $context + ['block' => $block['props'], 'block_id' => $block['id'], 'block_type' => $block['type']],
        );

        if (!$markers) {
            return $inner;
        }

        // Markers the designer preview uses to map clicks to blocks (never in the real e-mail/PDF).
        $attr = 'data-nwc-block="'.htmlspecialchars($block['id'], ENT_QUOTES).'"';

        if (BlockRegistry::KIND_MAIL === $kind) {
            // Mail blocks are table rows: a <div> around a <tr> is moved out of the table by the browser,
            // so the marker goes onto the block's first cell.
            if ('' === trim($inner)) {
                return '';
            }

            return (string) preg_replace('/<td\b/', '<td class="nwc-b nwc-b--'.$block['type'].'" '.$attr, $inner, 1);
        }

        return '<div class="nwc-b nwc-b--'.$block['type'].'" '.$attr.'>'.$inner.'</div>';
    }

    /**
     * Theme values resolved against the brand (empty colour = brand colour) and
     * expanded into the units the templates use.
     *
     * @param array<string, mixed> $theme
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function theme(array $theme, array $context): array
    {
        $brand = (array) ($context['brand'] ?? []);
        $primary = $theme['primary'] ?: (string) ($brand['color_primary'] ?? '#2a025f');
        $accent = $theme['accent'] ?: (string) ($brand['color_accent'] ?? '#a8f20d');

        $fonts = [
            'sans' => "'DejaVu Sans', Helvetica, Arial, sans-serif",
            'serif' => "'DejaVu Serif', Georgia, 'Times New Roman', serif",
            'system' => 'Helvetica, Arial, sans-serif',
        ];
        $radius = ['none' => '0', 's' => '1.5mm', 'm' => '3mm', 'l' => '5mm'];
        $radiusPx = ['none' => '0', 's' => '6px', 'm' => '12px', 'l' => '20px'];
        $margins = ['narrow' => '16mm 12mm 16mm 12mm', 'normal' => '19mm 15mm 17mm 15mm', 'wide' => '26mm 22mm 22mm 22mm'];
        $contentWidth = ['narrow' => 186, 'normal' => 180, 'wide' => 166];
        $mailSize = ['s' => '14px', 'm' => '16px', 'l' => '18px'];

        // array_replace, not +: the normalised theme already holds the empty colour keys
        return array_replace($theme, [
            'primary' => $primary,
            'accent' => $accent,
            'accent_ink' => self::isLight($accent) ? $primary : '#ffffff',
            'primary_ink' => self::isLight($primary) ? '#1a1a1a' : '#ffffff',
            'muted' => '#6b6b7b',
            'line' => '#e3e1ea',
            'font_family' => $fonts[$theme['font'] ?? 'sans'] ?? $fonts['sans'],
            // single quotes: also printed inside style="…" attributes
            'mono' => "'DejaVu Sans Mono', Menlo, Consolas, monospace",
            'r' => $radius[$theme['radius'] ?? 'm'] ?? $radius['m'],
            'rpx' => $radiusPx[$theme['radius'] ?? 'm'] ?? $radiusPx['m'],
            'page_margin' => $margins[$theme['margins'] ?? 'normal'] ?? $margins['normal'],
            // usable width in mm (A4) and the width of one card in a two-column layout; Dompdf needs absolute table widths
            'content_w' => $contentWidth[$theme['margins'] ?? 'normal'] ?? 180,
            'card_w' => (($contentWidth[$theme['margins'] ?? 'normal'] ?? 180) - 4) / 2,
            'font_size' => $mailSize[$theme['size'] ?? 'm'] ?? $mailSize['m'],
            'grade_bg' => [0 => '#cccccc', 1 => $accent, 2 => $accent, 3 => '#ffb020', 4 => '#ff6b73', 5 => '#ff6b73'],
            'grade_ink' => [0 => '#555555', 1 => self::isLight($accent) ? $primary : '#ffffff', 2 => self::isLight($accent) ? $primary : '#ffffff', 3 => '#1a1a1a', 4 => '#1a1a1a', 5 => '#1a1a1a'],
            'space' => ['none' => '0', 's' => '2mm', 'm' => '5mm', 'l' => '9mm', 'xl' => '14mm'],
            'space_px' => ['none' => '0', 's' => '8px', 'm' => '18px', 'l' => '28px', 'xl' => '44px'],
        ]);
    }

    /**
     * Editable Twig source equivalent to the design (frame + includes).
     *
     * @param array<string, mixed>|string|null $raw
     */
    public function toTwig(string $kind, array|string|null $raw): string
    {
        $design = Design::normalise($raw, $kind);
        $lines = [
            '{# Generated from the visual designer. Each block is an include; edit properties, reorder, or replace a block with your own HTML. #}',
            "{% extends '@Contao/neo_websitecheck/".$kind."_frame.html.twig' %}",
            '{% set theme = '.$this->twigLiteral($design['theme']).' %}',
            '{% block content %}',
        ];

        foreach ($design['blocks'] as $block) {
            $lines[] = sprintf(
                "  {%% include '@Contao/neo_websitecheck/blocks/%s/%s.html.twig' with {block: %s, block_id: '%s'} %%}",
                $kind,
                $block['type'],
                $this->twigLiteral($block['props']),
                $block['id'],
            );
        }

        $lines[] = '{% endblock %}';

        return implode("\n", $lines)."\n";
    }

    private function twigLiteral(mixed $value): string
    {
        if (\is_array($value)) {
            $parts = [];
            $list = array_is_list($value);

            foreach ($value as $k => $v) {
                $parts[] = $list ? $this->twigLiteral($v) : (preg_match('/^[a-z_][a-z0-9_]*$/i', (string) $k) ? $k : "'".addslashes((string) $k)."'").': '.$this->twigLiteral($v);
            }

            return ($list ? '[' : '{').implode(', ', $parts).($list ? ']' : '}');
        }

        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (\is_int($value) || \is_float($value)) {
            return (string) $value;
        }

        return "'".str_replace(["\\", "'", "\n"], ["\\\\", "\\'", '\n'], (string) $value)."'";
    }

    private static function isLight(string $hex): bool
    {
        $hex = ltrim($hex, '#');

        if (6 !== \strlen($hex)) {
            return false;
        }

        [$r, $g, $b] = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 150;
    }
}
