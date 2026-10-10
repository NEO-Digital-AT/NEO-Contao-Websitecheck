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

    /** Measured values of the speed area (Grader::speedKpis), each with a label and a short note. */
    public const KPIS = ['lcp', 'cls', 'tbt', 'inp', 'perf', 'bytes', 'requests'];

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

        // Words used by several blocks: grade words, area names, effort. The
        // customer writes them; "@…" defaults arrive in the configuration's language.
        $labels = [];

        foreach ([1, 2, 3, 4, 5] as $n) {
            $labels['grade_'.$n] = ['type' => 'text', 'default' => '@grade.'.$n, 'group' => 'labels'];
        }

        foreach (self::AREAS as $area) {
            $labels['area_'.$area] = ['type' => 'text', 'default' => '@area.'.$area, 'group' => 'labels'];
        }

        foreach (['small', 'medium', 'large'] as $effort) {
            $labels['effort_'.$effort] = ['type' => 'text', 'default' => '@measure.effort.'.$effort, 'group' => 'labels'];
        }

        foreach (self::KPIS as $kpi) {
            $labels['kpi_'.$kpi] = ['type' => 'text', 'default' => '@speed.kpi.'.$kpi, 'group' => 'labels'];
            $labels['kpi_'.$kpi.'_note'] = ['type' => 'text', 'default' => '@speed.kpi.'.$kpi.'_note', 'group' => 'labels'];
        }

        if (self::KIND_MAIL === $kind) {
            return $common + [
                'outer' => ['type' => 'color', 'default' => '#f1eff7'],
                'size' => ['type' => 'select', 'default' => 'm', 'options' => ['s', 'm', 'l']],
            ] + $labels;
        }

        return $common + [
            'margins' => ['type' => 'select', 'default' => 'normal', 'options' => ['narrow', 'normal', 'wide']],
            'doc_title' => ['type' => 'text', 'default' => '@report.title_plain'],
            'header' => ['type' => 'bool', 'default' => true],
            'header_text' => ['type' => 'text', 'default' => '@report.header_plain'],
            'footer' => ['type' => 'bool', 'default' => true],
            'footer_text' => ['type' => 'text', 'default' => '{{company}} · {{owner}}'],
            'page_numbers' => ['type' => 'bool', 'default' => true],
            'page_label' => ['type' => 'text', 'default' => '@report.page_short'],
        ] + $labels;
    }

    /**
     * Translator for "@key" defaults in a given content language (the
     * configuration's language, not necessarily the back-end language).
     *
     * @return callable(string): string
     */
    public function resolver(string $locale): callable
    {
        return fn (string $key): string => $this->label($key, $locale, '');
    }

    /**
     * Schema for the widget: blocks with translated labels, grouped, plus theme.
     *
     * @param string $locale        back-end language (labels of the designer UI)
     * @param string $contentLocale language of the configuration (defaults of new blocks)
     *
     * @return array<string, mixed>
     */
    public function schema(string $kind, string $locale, string $contentLocale = ''): array
    {
        $t = fn (string $key, string $fallback = ''): string => $this->label($key, $locale, $fallback);
        $resolve = $this->resolver('' !== $contentLocale ? $contentLocale : $locale);
        $blocks = [];

        foreach (self::blocks($kind) as $type => $def) {
            $props = [];

            foreach ($def['props'] as $name => $prop) {
                $entry = $prop + ['label' => $t('designer.block.'.$type.'.'.$name, $t('designer.prop.'.$name, $name))];
                $entry['default'] = Design::resolve($prop['default'] ?? null, $resolve);

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
            $entry = $prop + ['label' => $t('designer.theme.'.$name, $this->labelLabel($name, $locale))];
            $entry['default'] = Design::resolve($prop['default'] ?? null, $resolve);

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
            'themeGroups' => ['design' => $t('designer.ui.theme'), 'labels' => $t('designer.ui.labels')],
            'blocks' => $blocks,
            'theme' => $theme,
            'areas' => array_combine(self::AREAS, array_map(fn (string $a): string => $this->translator->trans('area.'.$a, [], 'neo_websitecheck', $locale), self::AREAS)),
        ];
    }

    /** UI label of a theme word field: "Grade word 3", "Area name: Speed", "Effort: small". */
    private function labelLabel(string $name, string $locale): string
    {
        if (preg_match('/^grade_(\d)$/', $name, $m)) {
            return sprintf($this->label('designer.theme.grade_word', $locale, 'Grade %s'), $m[1]);
        }

        if (str_starts_with($name, 'area_')) {
            return sprintf($this->label('designer.theme.area_name', $locale, 'Area %s'), $this->label('area.'.substr($name, 5), $locale, substr($name, 5)));
        }

        if (preg_match('/^kpi_([a-z]+)(_note)?$/', $name, $m)) {
            return sprintf($this->label('designer.theme.kpi_'.(isset($m[2]) ? 'note' : 'name'), $locale, 'KPI %s'), $this->label('speed.kpi.'.$m[1], $locale, $m[1]));
        }

        if (str_starts_with($name, 'effort_')) {
            return sprintf($this->label('designer.theme.effort_word', $locale, 'Effort %s'), $this->label('designer.opt.effort_'.substr($name, 7), $locale, substr($name, 7)));
        }

        return $name;
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

        // Every word printed by a block is one of its properties. Defaults
        // starting with "@" are translation keys, resolved in the language of
        // the configuration when the block is added; from then on it is text.
        return [
            // --- content -----------------------------------------------------
            'cover' => ['group' => 'content', 'props' => [
                'eyebrow' => $text('@report.cover_eyebrow'), 'title' => $text('@report.cover_title'), 'subtitle' => $area(),
                'show_domain' => $bool(), 'show_grade' => $bool(), 'grade_line' => $text('@report.grade_line_plain'),
                'show_meta' => $bool(), 'label_pages' => $text('@report.meta_pages'), 'label_date' => $text('@report.meta_date'), 'label_for' => $text('@report.meta_for'),
                'show_screens' => $bool(), 'label_desktop' => $text('@report.label_desktop'),
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
                'eyebrow' => $text('@report.next_eyebrow'), 'title' => $text('@report.next_title'), 'html' => $rich('<p>@report.next_text</p>'), 'promise' => $area('@report.next_promise'),
                'show_portrait' => $bool(), 'show_contact' => $bool(), 'space' => self::SPACE,
            ]],
            'signature' => ['group' => 'content', 'props' => ['html' => $rich('<p><b>{{owner}}</b><br>{{company}}<br>{{phone}}</p>'), 'show_portrait' => $bool(false), 'space' => self::SPACE]],
            // --- data --------------------------------------------------------
            'grade_box' => ['group' => 'data', 'props' => [
                'grade_line' => $text('@report.grade_line_plain'),
                'show_summary' => $bool(),
                'style' => ['type' => 'select', 'default' => 'primary', 'options' => ['primary', 'soft']],
                'space' => self::SPACE,
            ]],
            'meta_cards' => ['group' => 'data', 'props' => [
                'show_pages' => $bool(), 'label_pages' => $text('@report.meta_pages'),
                'show_date' => $bool(), 'label_date' => $text('@report.meta_date'),
                'show_for' => $bool(), 'label_for' => $text('@report.meta_for'),
                'space' => self::SPACE,
            ]],
            'screenshots' => ['group' => 'data', 'props' => [
                'layout' => ['type' => 'select', 'default' => 'montage', 'options' => ['montage', 'desktop', 'mobile', 'side']],
                'label_desktop' => $text('@report.label_desktop'), 'label_mobile' => $text('@report.label_mobile'),
                'space' => self::SPACE,
            ]],
            'gauges' => ['group' => 'data', 'props' => [
                'perf' => $bool(), 'label_perf' => $text('@fe.gauge_perf'),
                'a11y' => $bool(), 'label_a11y' => $text('@fe.gauge_a11y'),
                'bp' => $bool(), 'label_bp' => $text('@fe.gauge_bp'),
                'seo' => $bool(), 'label_seo' => $text('@fe.gauge_seo'),
                'show_kpis' => $bool(), 'space' => self::SPACE,
            ]],
            'areas' => ['group' => 'data', 'props' => [
                'eyebrow' => $text('@report.overview_eyebrow'), 'title' => $text('@report.overview_title'),
                'layout' => ['type' => 'select', 'default' => 'cards', 'options' => ['cards', 'list']],
                'show_meta' => $bool(),
                'legend' => $bool(), 'legend_title' => $text('@report.legend_title'), 'legend_text' => $area('@report.legend'),
                'space' => self::SPACE,
            ]],
            'measures' => ['group' => 'data', 'props' => [
                'eyebrow' => $text('@report.measures_eyebrow'), 'show_effort' => $bool(),
                'style' => ['type' => 'select', 'default' => 'primary', 'options' => ['primary', 'soft']],
                'max' => ['type' => 'number', 'default' => 3, 'min' => 1, 'max' => 10],
                'space' => self::SPACE,
            ]],
            'strengths' => ['group' => 'data', 'props' => ['eyebrow' => $text(), 'title' => $text('@report.what_works'), 'max' => ['type' => 'number', 'default' => 6, 'min' => 1, 'max' => 12], 'space' => self::SPACE]],
            'area_detail' => ['group' => 'data', 'props' => [
                'area' => ['type' => 'select', 'default' => 'accessibility', 'options' => self::AREAS],
                'show_grade' => $bool(), 'grade_line' => $text('@report.area_grade_line'),
                'title' => $text(), 'intro' => $area(),
                'show_crops' => $bool(), 'max_crops' => ['type' => 'number', 'default' => 3, 'min' => 0, 'max' => 4], 'show_kpis' => $bool(false),
                'works_title' => $text('@report.what_works'), 'improve_title' => $text('@report.what_to_improve'), 'nothing_text' => $text('@report.nothing_to_improve'),
                'max_issues' => ['type' => 'number', 'default' => 3, 'min' => 0, 'max' => 10],
                'max_good' => ['type' => 'number', 'default' => 2, 'min' => 0, 'max' => 10],
                'note' => $area(), 'space' => self::SPACE,
            ]],
            'kpis' => ['group' => 'data', 'props' => ['space' => self::SPACE]],
            'ai_sections' => ['group' => 'data', 'props' => [
                'eyebrow' => $text('@report.ai_sections_eyebrow'), 'title' => $text('@report.ai_sections_title'),
                'show_intro' => $bool(), 'space' => self::SPACE,
            ]],
            'pages_table' => ['group' => 'data', 'props' => [
                'eyebrow' => $text('@report.pages_eyebrow_plain'), 'title' => $text('@report.pages_title'), 'intro' => $area('@report.pages_intro'),
                'col_page' => $text('@report.col_page'), 'col_title' => $text('@report.col_title'), 'col_lcp' => $text('@report.col_lcp'),
                'col_contrast' => $text('@report.col_contrast'), 'col_grade' => $text('@report.col_grade'), 'none_text' => $text('@word.none'),
                'space' => self::SPACE,
            ]],
            'sources' => ['group' => 'data', 'props' => ['text' => $area('@report.sources_plain'), 'space' => self::SPACE]],
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
                'tagline' => $text('@report.header'),
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
                'label' => $text('@mail.cta'),
                'target' => ['type' => 'select', 'default' => 'contact', 'options' => ['contact', 'website', 'custom']],
                'url' => $text(), 'alt' => $text('@mail.cta_alt'),
                'style' => ['type' => 'select', 'default' => 'accent', 'options' => ['accent', 'primary', 'outline']],
                'align' => ['type' => 'align', 'default' => 'left'], 'space' => self::SPACE,
            ]],
            'pdf_note' => ['group' => 'content', 'props' => ['badge' => $text('PDF'), 'html' => $rich('<p>@mail.pdf_note</p>'), 'space' => self::SPACE]],
            'newsletter_note' => ['group' => 'content', 'props' => ['html' => $rich('<p>@mail.newsletter_note</p>'), 'space' => self::SPACE]],
            'signature' => ['group' => 'content', 'props' => ['html' => $rich('<p><b>{{owner}}</b><br>{{company}}<br>{{phone}} · {{website}}</p>'), 'show_portrait' => $bool(), 'space' => self::SPACE]],
            'footer' => ['group' => 'content', 'props' => ['html' => $rich('<p>@mail.footer_plain</p>')]],
            'grade_box' => ['group' => 'data', 'props' => ['grade_line' => $text('@report.grade_line_plain'), 'show_summary' => $bool(), 'space' => self::SPACE]],
            'areas' => ['group' => 'data', 'props' => ['title' => $text('@mail.owner_areas'), 'space' => self::SPACE]],
            'measures' => ['group' => 'data', 'props' => ['title' => $text('@mail.measures_title'), 'max' => ['type' => 'number', 'default' => 3, 'min' => 1, 'max' => 10], 'space' => self::SPACE]],
            'strengths' => ['group' => 'data', 'props' => ['title' => $text('@report.what_works'), 'max' => ['type' => 'number', 'default' => 4, 'min' => 1, 'max' => 12], 'space' => self::SPACE]],
            'spacer' => ['group' => 'layout', 'props' => ['height' => ['type' => 'number', 'default' => 16, 'min' => 4, 'max' => 120, 'unit' => 'px']]],
            'divider' => ['group' => 'layout', 'props' => ['space' => self::SPACE]],
        ];
    }
}
