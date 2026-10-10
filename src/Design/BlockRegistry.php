<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Design;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Single source of truth for the visual designer: which blocks exist for the
 * PDF report and for the e-mail, which properties each block has (type,
 * default, options) and how they are labelled. The back-end widget renders its
 * palette and property panels from schema(), the DesignRenderer normalises
 * stored designs against the same definitions, and each block has a Twig
 * partial in contao/templates/neo_websitecheck/blocks/{pdf|mail}/{type}.html.twig.
 *
 * Property types: text, textarea, richtext, select, bool, color, number, image, align.
 */
final class BlockRegistry
{
    public const KIND_PDF = 'pdf';
    public const KIND_MAIL = 'mail';

    public const AREAS = ['speed', 'accessibility', 'seo', 'ai', 'content', 'legal', 'mobile'];

    /** Spacing after a block (rendered as margin-bottom). */
    private const SPACE = ['type' => 'select', 'default' => 'm', 'options' => ['none', 's', 'm', 'l', 'xl']];

    /** Background of a block. */
    private const BG = ['type' => 'select', 'default' => 'none', 'options' => ['none', 'soft', 'primary', 'accent']];

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * @return array<string, array{group: string, props: array<string, array<string, mixed>>}>
     */
    public static function blocks(string $kind): array
    {
        return self::KIND_MAIL === $kind ? self::mailBlocks() : self::pdfBlocks();
    }

    /**
     * Design-level settings (theme) per kind.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function theme(string $kind): array
    {
        $common = [
            'primary' => ['type' => 'color', 'default' => ''],
            'accent' => ['type' => 'color', 'default' => ''],
            'text' => ['type' => 'color', 'default' => '#222222'],
            'soft' => ['type' => 'color', 'default' => '#f5f3fb'],
            'font' => ['type' => 'select', 'default' => 'sans', 'options' => ['sans', 'serif', 'system']],
            'radius' => ['type' => 'select', 'default' => 'm', 'options' => ['none', 's', 'm', 'l']],
        ];

        if (self::KIND_MAIL === $kind) {
            return $common + [
                'outer' => ['type' => 'color', 'default' => '#f1eff7'],
                'size' => ['type' => 'select', 'default' => 'm', 'options' => ['s', 'm', 'l']],
            ];
        }

        return $common + [
            'margins' => ['type' => 'select', 'default' => 'normal', 'options' => ['narrow', 'normal', 'wide']],
            'header' => ['type' => 'bool', 'default' => true],
            'header_text' => ['type' => 'text', 'default' => ''],
            'footer' => ['type' => 'bool', 'default' => true],
            'footer_text' => ['type' => 'text', 'default' => '{{company}} · {{owner}}'],
            'page_numbers' => ['type' => 'bool', 'default' => true],
        ];
    }

    /**
     * Schema for the widget: blocks with translated labels, grouped, plus theme.
     *
     * @return array<string, mixed>
     */
    public function schema(string $kind, string $locale): array
    {
        $t = fn (string $key, string $fallback = ''): string => $this->label($key, $locale, $fallback);
        $blocks = [];

        foreach (self::blocks($kind) as $type => $def) {
            $props = [];

            foreach ($def['props'] as $name => $prop) {
                $entry = $prop + ['label' => $t('designer.block.'.$type.'.'.$name, $t('designer.prop.'.$name, $name))];

                if (isset($prop['options'])) {
                    $entry['options'] = [];

                    foreach ($prop['options'] as $value) {
                        $entry['options'][$value] = $t('designer.block.'.$type.'.'.$name.'.'.$value, $t('designer.opt.'.$value, (string) $value));
                    }
                }

                $props[$name] = $entry;
            }

            $blocks[$type] = [
                'group' => $def['group'],
                'label' => $t('designer.block.'.$type.'.label', $type),
                'desc' => $t('designer.block.'.$type.'.desc', ''),
                'props' => $props,
            ];
        }

        $theme = [];

        foreach (self::theme($kind) as $name => $prop) {
            $entry = $prop + ['label' => $t('designer.theme.'.$name, $name)];

            if (isset($prop['options'])) {
                $entry['options'] = [];

                foreach ($prop['options'] as $value) {
                    $entry['options'][$value] = $t('designer.theme.'.$name.'.'.$value, $t('designer.opt.'.$value, (string) $value));
                }
            }

            $theme[$name] = $entry;
        }

        return [
            'kind' => $kind,
            'groups' => ['content' => $t('designer.group.content'), 'data' => $t('designer.group.data'), 'layout' => $t('designer.group.layout')],
            'blocks' => $blocks,
            'theme' => $theme,
            'areas' => array_combine(self::AREAS, array_map(fn (string $a): string => $this->translator->trans('area.'.$a, [], 'neo_websitecheck', $locale), self::AREAS)),
        ];
    }

    private function label(string $key, string $locale, string $fallback): string
    {
        $value = $this->translator->trans($key, [], 'neo_websitecheck', $locale);

        return $value === $key ? $fallback : $value;
    }

    /** @return array<string, array{group: string, props: array<string, array<string, mixed>>}> */
    private static function pdfBlocks(): array
    {
        $text = fn (string $default = ''): array => ['type' => 'text', 'default' => $default];
        $area = fn (string $default = ''): array => ['type' => 'textarea', 'default' => $default];
        $rich = fn (string $default = ''): array => ['type' => 'richtext', 'default' => $default];
        $bool = fn (bool $default = true): array => ['type' => 'bool', 'default' => $default];

        return [
            // --- content -----------------------------------------------------
            'cover' => ['group' => 'content', 'props' => [
                'eyebrow' => $text(), 'title' => $text(), 'subtitle' => $area(),
                'show_domain' => $bool(), 'show_grade' => $bool(), 'show_meta' => $bool(), 'show_screens' => $bool(),
                'logo' => ['type' => 'select', 'default' => 'auto', 'options' => ['auto', 'none']],
                'bg' => ['type' => 'select', 'default' => 'white', 'options' => ['white', 'primary', 'soft']],
                'image' => ['type' => 'image', 'default' => ''],
            ]],
            'heading' => ['group' => 'content', 'props' => [
                'eyebrow' => $text(), 'text' => $text(),
                'level' => ['type' => 'select', 'default' => 'h2', 'options' => ['h1', 'h2', 'h3']],
                'align' => ['type' => 'align', 'default' => 'left'],
                'space' => self::SPACE,
            ]],
            'text' => ['group' => 'content', 'props' => [
                'html' => $rich(),
                'size' => ['type' => 'select', 'default' => 'm', 'options' => ['s', 'm', 'l']],
                'tone' => ['type' => 'select', 'default' => 'normal', 'options' => ['normal', 'muted', 'primary']],
                'align' => ['type' => 'align', 'default' => 'left'],
                'columns' => ['type' => 'select', 'default' => '1', 'options' => ['1', '2']],
                'bg' => self::BG, 'space' => self::SPACE,
            ]],
            'image' => ['group' => 'content', 'props' => [
                'src' => ['type' => 'image', 'default' => ''],
                'width' => ['type' => 'number', 'default' => 100, 'min' => 10, 'max' => 100, 'unit' => '%'],
                'align' => ['type' => 'align', 'default' => 'left'],
                'caption' => $text(), 'rounded' => $bool(true), 'space' => self::SPACE,
            ]],
            'note' => ['group' => 'content', 'props' => ['html' => $rich(), 'space' => self::SPACE]],
            'next_steps' => ['group' => 'content', 'props' => [
                'eyebrow' => $text(), 'title' => $text(), 'html' => $rich(), 'promise' => $area(),
                'show_portrait' => $bool(), 'show_contact' => $bool(), 'space' => self::SPACE,
            ]],
            'signature' => ['group' => 'content', 'props' => ['html' => $rich(), 'show_portrait' => $bool(false), 'space' => self::SPACE]],
            // --- data --------------------------------------------------------
            'grade_box' => ['group' => 'data', 'props' => [
                'show_summary' => $bool(),
                'style' => ['type' => 'select', 'default' => 'primary', 'options' => ['primary', 'soft']],
                'space' => self::SPACE,
            ]],
            'meta_cards' => ['group' => 'data', 'props' => ['show_pages' => $bool(), 'show_date' => $bool(), 'show_for' => $bool(), 'space' => self::SPACE]],
            'screenshots' => ['group' => 'data', 'props' => [
                'layout' => ['type' => 'select', 'default' => 'montage', 'options' => ['montage', 'desktop', 'mobile', 'side']],
                'space' => self::SPACE,
            ]],
            'gauges' => ['group' => 'data', 'props' => ['perf' => $bool(), 'a11y' => $bool(), 'bp' => $bool(), 'seo' => $bool(), 'show_kpis' => $bool(), 'space' => self::SPACE]],
            'areas' => ['group' => 'data', 'props' => [
                'eyebrow' => $text(), 'title' => $text(),
                'layout' => ['type' => 'select', 'default' => 'cards', 'options' => ['cards', 'list']],
                'legend' => $bool(), 'space' => self::SPACE,
            ]],
            'measures' => ['group' => 'data', 'props' => [
                'eyebrow' => $text(), 'show_effort' => $bool(),
                'style' => ['type' => 'select', 'default' => 'primary', 'options' => ['primary', 'soft']],
                'max' => ['type' => 'number', 'default' => 3, 'min' => 1, 'max' => 10],
                'space' => self::SPACE,
            ]],
            'strengths' => ['group' => 'data', 'props' => ['eyebrow' => $text(), 'title' => $text(), 'max' => ['type' => 'number', 'default' => 6, 'min' => 1, 'max' => 12], 'space' => self::SPACE]],
            'area_detail' => ['group' => 'data', 'props' => [
                'area' => ['type' => 'select', 'default' => 'accessibility', 'options' => self::AREAS],
                'show_grade' => $bool(), 'title' => $text(), 'intro' => $area(),
                'show_crops' => $bool(), 'show_kpis' => $bool(false),
                'max_issues' => ['type' => 'number', 'default' => 3, 'min' => 0, 'max' => 10],
                'max_good' => ['type' => 'number', 'default' => 2, 'min' => 0, 'max' => 10],
                'note' => $area(), 'space' => self::SPACE,
            ]],
            'kpis' => ['group' => 'data', 'props' => ['space' => self::SPACE]],
            'pages_table' => ['group' => 'data', 'props' => ['eyebrow' => $text(), 'title' => $text(), 'intro' => $area(), 'space' => self::SPACE]],
            'sources' => ['group' => 'data', 'props' => ['space' => self::SPACE]],
            // --- layout ------------------------------------------------------
            'spacer' => ['group' => 'layout', 'props' => ['height' => ['type' => 'number', 'default' => 8, 'min' => 1, 'max' => 120, 'unit' => 'mm']]],
            'divider' => ['group' => 'layout', 'props' => ['space' => self::SPACE]],
            'page_break' => ['group' => 'layout', 'props' => []],
        ];
    }

    /** @return array<string, array{group: string, props: array<string, array<string, mixed>>}> */
    private static function mailBlocks(): array
    {
        $text = fn (string $default = ''): array => ['type' => 'text', 'default' => $default];
        $area = fn (string $default = ''): array => ['type' => 'textarea', 'default' => $default];
        $rich = fn (string $default = ''): array => ['type' => 'richtext', 'default' => $default];
        $bool = fn (bool $default = true): array => ['type' => 'bool', 'default' => $default];

        return [
            'header' => ['group' => 'content', 'props' => [
                'logo' => ['type' => 'select', 'default' => 'auto', 'options' => ['auto', 'text', 'none']],
                'tagline' => $text(),
                'bg' => ['type' => 'select', 'default' => 'primary', 'options' => ['primary', 'accent', 'white']],
                'image' => ['type' => 'image', 'default' => ''],
            ]],
            'heading' => ['group' => 'content', 'props' => [
                'text' => $text(),
                'level' => ['type' => 'select', 'default' => 'h2', 'options' => ['h1', 'h2', 'h3']],
                'align' => ['type' => 'align', 'default' => 'left'], 'space' => self::SPACE,
            ]],
            'text' => ['group' => 'content', 'props' => [
                'html' => $rich(),
                'size' => ['type' => 'select', 'default' => 'm', 'options' => ['s', 'm', 'l']],
                'tone' => ['type' => 'select', 'default' => 'normal', 'options' => ['normal', 'muted', 'primary']],
                'align' => ['type' => 'align', 'default' => 'left'],
                'bg' => self::BG, 'space' => self::SPACE,
            ]],
            'image' => ['group' => 'content', 'props' => [
                'src' => ['type' => 'image', 'default' => ''],
                'width' => ['type' => 'number', 'default' => 100, 'min' => 10, 'max' => 100, 'unit' => '%'],
                'align' => ['type' => 'align', 'default' => 'left'], 'rounded' => $bool(true), 'space' => self::SPACE,
            ]],
            'button' => ['group' => 'content', 'props' => [
                'label' => $text(),
                'target' => ['type' => 'select', 'default' => 'contact', 'options' => ['contact', 'website', 'custom']],
                'url' => $text(), 'alt' => $text(),
                'style' => ['type' => 'select', 'default' => 'accent', 'options' => ['accent', 'primary', 'outline']],
                'align' => ['type' => 'align', 'default' => 'left'], 'space' => self::SPACE,
            ]],
            'pdf_note' => ['group' => 'content', 'props' => ['html' => $rich(), 'space' => self::SPACE]],
            'newsletter_note' => ['group' => 'content', 'props' => ['html' => $rich(), 'space' => self::SPACE]],
            'signature' => ['group' => 'content', 'props' => ['html' => $rich(), 'show_portrait' => $bool(), 'space' => self::SPACE]],
            'footer' => ['group' => 'content', 'props' => ['html' => $rich()]],
            'grade_box' => ['group' => 'data', 'props' => ['show_summary' => $bool(), 'space' => self::SPACE]],
            'areas' => ['group' => 'data', 'props' => ['title' => $text(), 'space' => self::SPACE]],
            'measures' => ['group' => 'data', 'props' => ['title' => $text(), 'max' => ['type' => 'number', 'default' => 3, 'min' => 1, 'max' => 10], 'space' => self::SPACE]],
            'strengths' => ['group' => 'data', 'props' => ['title' => $text(), 'max' => ['type' => 'number', 'default' => 4, 'min' => 1, 'max' => 12], 'space' => self::SPACE]],
            'spacer' => ['group' => 'layout', 'props' => ['height' => ['type' => 'number', 'default' => 16, 'min' => 4, 'max' => 120, 'unit' => 'px']]],
            'divider' => ['group' => 'layout', 'props' => ['space' => self::SPACE]],
        ];
    }
}
