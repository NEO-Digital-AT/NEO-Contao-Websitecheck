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
 * toTwig() produces the same document as one editable Twig source for the
 * "advanced" mode: frame, CSS, every block's HTML and every word.
 */
final class DesignRenderer
{
    public function __construct(
        private readonly Environment $twig,
        private readonly BlockRegistry $registry,
    ) {
    }

    /**
     * @param array<string, mixed>|string|null $raw     stored design JSON
     * @param array<string, mixed>             $context ReportRenderer::context() (+ vars at top level)
     */
    public function render(string $kind, array|string|null $raw, array $context, bool $markers = false): string
    {
        $resolve = $this->registry->resolver((string) ($context['locale'] ?? 'en'));
        $context = self::withLabels(Design::normalise($raw, $kind, $resolve)['theme'], $context);
        $design = Design::prepare($raw, $kind, (array) $context['vars'], $resolve);
        $context['t'] = self::theme($design['theme'], $context);
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
     * Placeholders that depend on the design's own words: {{grade_word}} is the
     * customer's grade word (not the bundle's), {{lighthouse}} the version.
     *
     * @param array<string, mixed> $theme
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public static function withLabels(array $theme, array $context): array
    {
        $grade = (int) ($context['result']['grade'] ?? 0);
        $vars = (array) ($context['vars'] ?? []);

        if ($grade > 0 && '' !== trim((string) ($theme['grade_'.$grade] ?? ''))) {
            $vars['grade_word'] = (string) $theme['grade_'.$grade];
        }

        $vars['lighthouse'] = (string) (($context['result']['lighthouse'] ?? '') ?: '–');
        $context['vars'] = $vars;

        return array_replace($context, $vars);
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
    public static function theme(array $theme, array $context): array
    {
        $brand = (array) ($context['brand'] ?? []);
        $primary = $theme['primary'] ?: (string) ($brand['color_primary'] ?? '#2a025f');
        $accent = $theme['accent'] ?: (string) ($brand['color_accent'] ?? '#a8f20d');

        $fonts = [
            // Arimo: metric-compatible with Arial, bundled for the PDF (resources/fonts); e-mail clients fall back to Arial
            'arimo' => "Arimo, Arial, Helvetica, sans-serif",
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
        $labels = ['grade' => [0 => '–'], 'area' => [], 'effort' => [], 'kpi' => [], 'kpi_note' => []];

        foreach ($theme as $key => $value) {
            if (preg_match('/^kpi_([a-z]+)_note$/', (string) $key, $m)) {
                $labels['kpi_note'][$m[1]] = (string) $value;
            } elseif (preg_match('/^kpi_([a-z]+)$/', (string) $key, $m)) {
                $labels['kpi'][$m[1]] = (string) $value;
            } elseif (preg_match('/^grade_(\d)$/', (string) $key, $m)) {
                $labels['grade'][(int) $m[1]] = (string) $value;
            } elseif (str_starts_with((string) $key, 'area_')) {
                $labels['area'][substr((string) $key, 5)] = (string) $value;
            } elseif (str_starts_with((string) $key, 'effort_')) {
                $labels['effort'][substr((string) $key, 7)] = (string) $value;
            }
        }

        return array_replace($theme, [
            'labels' => $labels,
            'primary' => $primary,
            'accent' => $accent,
            'accent_ink' => self::isLight($accent) ? $primary : '#ffffff',
            'primary_ink' => self::isLight($primary) ? '#1a1a1a' : '#ffffff',
            'muted' => '#6b6b7b',
            'line' => '#e3e1ea',
            'font_family' => $fonts[$theme['font'] ?? 'arimo'] ?? $fonts['arimo'],
            // bottom padding that centres digits in grade badges (measured with dev/check-pdf-badges.py)
            'badge_lift' => ['arimo' => '.065em'][$theme['font'] ?? 'arimo'] ?? '0',
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
     * wc_theme() in a Twig template: the theme map written in the template,
     * expanded exactly like a stored design (placeholders, brand colours, labels).
     *
     * @param array<string, mixed> $theme
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public static function twigTheme(string $kind, array $theme, array $context, callable|null $resolve = null): array
    {
        $raw = ['theme' => $theme, 'blocks' => []];
        $context = self::withLabels(Design::normalise($raw, $kind, $resolve)['theme'], $context);

        return self::theme(Design::prepare($raw, $kind, (array) $context['vars'], $resolve)['theme'], $context);
    }

    /**
     * wc_block() in a Twig template: the properties of one block, validated
     * and with placeholders filled, as the block partials expect them.
     *
     * @param array<string, mixed> $props
     * @param array<string, mixed> $t       result of wc_theme()
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public static function twigBlock(string $kind, string $type, array $props, array $t, array $context, callable|null $resolve = null): array
    {
        $vars = (array) ($context['vars'] ?? []);
        $grade = (int) ($context['result']['grade'] ?? 0);

        if ($grade > 0 && '' !== trim((string) ($t['labels']['grade'][$grade] ?? ''))) {
            $vars['grade_word'] = (string) $t['labels']['grade'][$grade];
        }

        $vars['lighthouse'] = (string) (($context['result']['lighthouse'] ?? '') ?: '–');
        $design = Design::prepare(['blocks' => [['id' => 'b', 'type' => $type, 'props' => $props]]], $kind, $vars, $resolve);

        return $design['blocks'][0]['props'] ?? [];
    }

    /**
     * The complete document as one editable Twig source (advanced mode): the
     * frame with its CSS, then every block with its words in a wc_block() map
     * followed by the block's HTML. Rendering it gives the same document as
     * the visual design; from then on the customer edits code and words freely.
     *
     * @param array<string, mixed>|string|null $raw
     */
    public function toTwig(string $kind, array|string|null $raw, string $locale = 'en'): string
    {
        $design = Design::normalise($raw, $kind, $this->registry->resolver($locale));
        $loader = $this->twig->getLoader();
        $imports = [];
        $body = [];

        foreach ($design['blocks'] as $block) {
            $source = $loader->getSourceContext('@Contao/neo_websitecheck/blocks/'.$kind.'/'.$block['type'].'.html.twig')->getCode();
            $source = (string) preg_replace_callback('/\{%-?\s*import\s.*?-?%\}\s*/s', static function (array $m) use (&$imports): string {
                $imports[trim($m[0])] = true;

                return '';
            }, $source);
            $source = (string) preg_replace('/\{%-?\s*trans_default_domain\s.*?-?%\}\s*/s', '', $source);

            $body[] = sprintf(
                "{# ---- %s ---------------------------------------------------- #}\n{%% set block = wc_block('%s', '%s', t, %s) %%}\n%s",
                $block['type'],
                $kind,
                $block['type'],
                $this->twigLiteral($block['props'], 0),
                trim($source),
            );
        }

        $frame = $loader->getSourceContext('@Contao/neo_websitecheck/'.$kind.'_frame.html.twig')->getCode();
        $frame = (string) preg_replace('/\{#.*?#\}\s*/s', '', $frame, 1);
        $frame = str_replace(
            '{% set t = t|default(theme is defined ? theme : {}) %}',
            "{# Design: colours, font, margins and the words several blocks share (grade words, area names, effort, measured values). #}\n{% set t = wc_theme('".$kind."', ".$this->twigLiteral($design['theme'], 0).') %}',
            $frame,
        );
        $frame = str_replace('{% block content %}{{ content|raw }}{% endblock %}', implode("\n\n", $body), $frame);

        return "{#\n  Generated from the visual designer. Everything in this document is yours:\n  the words in the wc_theme() and wc_block() maps, the HTML and the CSS.\n  Placeholders such as {{firstname}} inside those words are filled in;\n  data from the check is available as result, areas, brand, images, crops.\n#}\n"
            .([] !== $imports ? implode("\n", array_keys($imports))."\n" : '').ltrim($frame);
    }

    /** Twig literal of a value; with $indent the top-level map is written one entry per line. */
    private function twigLiteral(mixed $value, int|null $indent = null): string
    {
        if ($value instanceof \Stringable) {
            $value = (string) $value;
        }

        if (\is_array($value)) {
            if ([] === $value) {
                return '{}';
            }

            $parts = [];
            $list = array_is_list($value);

            foreach ($value as $k => $v) {
                $parts[] = $list ? $this->twigLiteral($v) : (preg_match('/^[a-z_][a-z0-9_]*$/i', (string) $k) ? $k : "'".addslashes((string) $k)."'").': '.$this->twigLiteral($v);
            }

            if (null !== $indent && !$list) {
                $pad = str_repeat('    ', $indent + 1);

                return "{\n".$pad.implode(",\n".$pad, $parts)."\n".str_repeat('    ', $indent).'}';
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
