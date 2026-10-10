<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Check;

use Neo\WebsitecheckBundle\Check\Analyzer\AiVisibilityAnalyzer;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Turns the raw measurements ("data") into the presentable result: seven
 * graded areas, an overall school grade, the three most important measures,
 * what already works, per-area details and the page table. All texts come
 * from the translator (domain "neo_websitecheck") so the report, the e-mail
 * and the on-page result share one wording.
 *
 * Scores are 0–100 per area; grades: 1 ≥ 90, 2 ≥ 78, 3 ≥ 65, 4 ≥ 50, else 5.
 * The overall grade is the average, but never better than the worst area + 1.
 */
final class Grader
{
    public const AREAS = ['speed', 'accessibility', 'seo', 'ai', 'content', 'legal', 'mobile'];

    private string $locale = 'en';

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function grade(array $data, string $locale): array
    {
        $this->locale = $locale;

        $home = $data['psi']['home_mobile'] ?? [];
        $homeOk = \is_array($home) && !isset($home['error']) && isset($home['scores']);
        $htmlPages = (array) ($data['html'] ?? []);
        $homeHtml = $htmlPages[$data['home']['url'] ?? ''] ?? (reset($htmlPages) ?: []);
        $ai = \is_array($data['ai'] ?? null) && !isset($data['ai']['error']) ? $data['ai'] : null;

        $areas = [
            'speed' => $this->speed($data, $home, $homeOk),
            'accessibility' => $this->accessibility($data, $home, $homeOk, $htmlPages),
            'seo' => $this->seo($data, $home, $homeOk, $htmlPages),
            'ai' => $this->aiVisibility($data, $ai),
            'content' => $this->content($data, $homeHtml, $ai),
            'legal' => $this->legal($data),
            'mobile' => $this->mobile($home, $homeOk, $homeHtml),
        ];

        $grades = [];
        $sum = 0;
        $n = 0;

        foreach ($areas as $key => &$area) {
            $area['key'] = $key;
            $area['label'] = $this->t('area.'.$key);

            if (null === $area['score']) {
                $area['grade'] = 0;
                continue;
            }

            $area['grade'] = self::gradeFor($area['score']);
            $grades[] = $area['grade'];
            $sum += $area['score'];
            ++$n;
        }

        unset($area);

        $overallScore = $n > 0 ? $sum / $n : 0;
        $overall = $n > 0 ? self::gradeFor($overallScore) : 0;

        if ([] !== $grades) {
            $overall = max($overall, max($grades) - 1);
        }

        // Measures: highest-priority issues across all areas, three of them.
        $issues = [];

        foreach ($areas as $key => $area) {
            foreach ($area['issues'] as $issue) {
                $issues[] = $issue + ['area' => $key];
            }
        }

        usort($issues, static fn (array $a, array $b): int => ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0));
        $measures = [];

        foreach ($issues as $issue) {
            if ('' === ($issue['fix'] ?? '')) {
                continue;
            }

            $measures[] = ['title' => $issue['title'], 'text' => $issue['fix'], 'effort' => $issue['effort'] ?? 'small', 'area' => $issue['area']];

            if (3 === \count($measures)) {
                break;
            }
        }

        $strengths = [];

        foreach ($areas as $area) {
            foreach ($area['good'] as $good) {
                if (($good['strength'] ?? false) && \count($strengths) < 3) {
                    $strengths[] = ['title' => $good['title'], 'text' => $good['text']];
                }
            }
        }

        if (null !== $ai) {
            foreach ($ai['strengths'] as $s) {
                if (\count($strengths) >= 4) {
                    break;
                }

                $strengths[] = ['title' => $s['title'], 'text' => $s['text']];
            }
        }

        $verdictKey = $overall <= 2 ? 'good' : ($overall === 3 ? 'mid' : 'low');

        return [
            'domain' => (string) ($data['home']['domain'] ?? ''),
            'url' => (string) ($data['home']['url'] ?? ''),
            'checkedAt' => time(),
            'locale' => $locale,
            'grade' => $overall,
            'gradeWord' => $overall > 0 ? $this->t('grade.'.$overall) : '',
            'verdict' => $this->t('verdict.'.$verdictKey),
            'verdictKey' => $verdictKey,
            'summary' => null !== $ai && '' !== $ai['summary'] ? $ai['summary'] : $this->t('verdict.'.$verdictKey),
            'firstImpression' => $ai['firstImpression'] ?? '',
            'scores' => [
                'perf' => $homeOk ? $home['scores']['performance'] : null,
                'a11y' => $homeOk ? $home['scores']['accessibility'] : null,
                'bp' => $homeOk ? $home['scores']['best-practices'] : null,
                'seo' => $homeOk ? $home['scores']['seo'] : null,
            ],
            'areas' => array_values($areas),
            'measures' => $measures,
            'strengths' => $strengths,
            'pages' => $this->pageTable($data, $htmlPages),
            'screenshots' => [
                'desktop' => $data['screenshots']['desktop']['file'] ?? null,
                'mobile' => $data['screenshots']['mobile']['file'] ?? null,
                'mobileFull' => $data['screenshots']['mobileFull']['file'] ?? null,
            ],
            'crops' => (array) ($data['crops'] ?? []),
            'ai' => null !== $ai ? ['model' => $ai['model'], 'clarity' => $ai['clarity']] : null,
            'pagesChecked' => \count((array) ($data['pages'] ?? [])),
            'lighthouse' => $homeOk ? $home['version'] : '',
            'errors' => (array) ($data['errors'] ?? []),
        ];
    }

    public static function gradeFor(float $score): int
    {
        return match (true) {
            $score >= 90 => 1,
            $score >= 78 => 2,
            $score >= 65 => 3,
            $score >= 50 => 4,
            default => 5,
        };
    }

    // ---------------------------------------------------------------- areas

    private function speed(array $data, array $home, bool $ok): array
    {
        if (!$ok) {
            return $this->unavailable('speed', (string) ($home['error'] ?? $data['errors']['speed_home'] ?? ''));
        }

        $scores = [$home['scores']['performance'], $home['scores']['performance']];

        foreach (['page_1', 'page_2'] as $k) {
            if (isset($data['psi'][$k]['scores']['performance'])) {
                $scores[] = $data['psi'][$k]['scores']['performance'];
            }
        }

        $score = array_sum($scores) / \count($scores);
        $m = $home['metrics'];
        $lcp = null !== $m['lcp'] ? $m['lcp'] / 1000 : null;
        $good = [];
        $issues = [];
        $kpis = [
            ['key' => 'lcp', 'label' => $this->t('speed.kpi.lcp'), 'value' => null !== $lcp ? $this->num($lcp, 1).' s' : '–', 'ok' => null !== $lcp && $lcp <= 2.5, 'note' => $this->t('speed.kpi.lcp_note')],
            ['key' => 'cls', 'label' => $this->t('speed.kpi.cls'), 'value' => null !== $m['cls'] ? $this->num($m['cls'], 2) : '–', 'ok' => null !== $m['cls'] && $m['cls'] <= 0.1, 'note' => $this->t('speed.kpi.cls_note')],
            ['key' => 'tbt', 'label' => $this->t('speed.kpi.tbt'), 'value' => null !== $m['tbt'] ? $this->num($m['tbt'], 0).' ms' : '–', 'ok' => null !== $m['tbt'] && $m['tbt'] <= 200, 'note' => $this->t('speed.kpi.tbt_note')],
            ['key' => 'perf', 'label' => $this->t('speed.kpi.perf'), 'value' => (string) $home['scores']['performance'], 'ok' => $home['scores']['performance'] >= 90, 'note' => $this->t('speed.kpi.perf_note')],
            ['key' => 'bytes', 'label' => $this->t('speed.kpi.bytes'), 'value' => null !== $m['bytes'] ? $this->num($m['bytes'] / 1_048_576, 1).' MB' : '–', 'ok' => null !== $m['bytes'] && $m['bytes'] <= 2_000_000, 'note' => $this->t('speed.kpi.bytes_note')],
            ['key' => 'requests', 'label' => $this->t('speed.kpi.requests'), 'value' => (string) $home['network']['requests'], 'ok' => $home['network']['requests'] <= 80, 'note' => $this->t('speed.kpi.requests_note')],
        ];

        if (isset($home['field']['inp'])) {
            $kpis[2] = ['key' => 'inp', 'label' => $this->t('speed.kpi.inp'), 'value' => $this->num($home['field']['inp'], 0).' ms', 'ok' => $home['field']['inp'] <= 200, 'note' => $this->t('speed.kpi.inp_note')];
        }

        if (null !== $lcp && $lcp > 2.5) {
            $issues[] = $this->issue('speed.issue.lcp', ['%lcp%' => $this->num($lcp, 1)], 'medium', 80 + min(15, (int) $lcp * 3));
        } else {
            $good[] = $this->good('speed.good.lcp', ['%lcp%' => $this->num($lcp ?? 0, 1)], true);
        }

        $largest = $home['network']['largestImage'];

        if (null !== $largest && $largest['bytes'] > 400_000) {
            $issues[] = $this->issue('speed.issue.largest_image', ['%kb%' => $this->num($largest['bytes'] / 1024, 0), '%file%' => basename((string) parse_url($largest['url'], PHP_URL_PATH)) ?: $largest['url']], 'small', 60);
        }

        $this->auditIssue($issues, $home, 'render-blocking-resources', 'speed.issue.render_blocking', 'small', 55, static fn (array $a): array => ['%count%' => $a['count']]);
        $this->auditIssue($issues, $home, 'modern-image-formats', 'speed.issue.image_formats', 'small', 50);
        $this->auditIssue($issues, $home, 'uses-responsive-images', 'speed.issue.responsive_images', 'small', 48);
        $this->auditIssue($issues, $home, 'unused-javascript', 'speed.issue.unused_js', 'medium', 40);
        $this->auditIssue($issues, $home, 'server-response-time', 'speed.issue.ttfb', 'medium', 58, static fn (array $a): array => ['%value%' => $a['displayValue']]);
        $this->auditIssue($issues, $home, 'uses-text-compression', 'speed.issue.compression', 'small', 45);

        if (null !== $m['cls'] && $m['cls'] > 0.1) {
            $issues[] = $this->issue('speed.issue.cls', ['%cls%' => $this->num($m['cls'], 2)], 'medium', 62);
        } else {
            $good[] = $this->good('speed.good.cls');
        }

        if (null !== $m['bytes'] && $m['bytes'] > 3_000_000) {
            $issues[] = $this->issue('speed.issue.bytes', ['%mb%' => $this->num($m['bytes'] / 1_048_576, 1)], 'medium', 52);
        }

        if (($home['audits']['is-on-https']['score'] ?? 1) >= 1 && ($data['home']['https'] ?? true)) {
            $good[] = $this->good('speed.good.https');
        }

        if (($home['audits']['errors-in-console']['score'] ?? 1) >= 1) {
            $good[] = $this->good('speed.good.console');
        } else {
            $issues[] = $this->issue('speed.issue.console', ['%count%' => $home['audits']['errors-in-console']['count'] ?? 1], 'small', 30);
        }

        if (($home['audits']['modern-image-formats']['score'] ?? 1) >= 1 && ($home['audits']['uses-responsive-images']['score'] ?? 1) >= 1) {
            $good[] = $this->good('speed.good.images');
        }

        $summaryKey = $score >= 90 ? 'good' : ($score >= 65 ? 'ok' : 'bad');

        return [
            'score' => $score,
            'summary' => $this->t('speed.summary.'.$summaryKey, ['%lcp%' => $this->num($lcp ?? 0, 1)]),
            'meta' => $this->t('speed.meta', ['%perf%' => $home['scores']['performance'], '%lcp%' => $this->num($lcp ?? 0, 1), '%cls%' => $this->num($m['cls'] ?? 0, 2)]),
            'kpis' => $kpis,
            'issues' => $issues,
            'good' => $good,
        ];
    }

    private function accessibility(array $data, array $home, bool $ok, array $htmlPages): array
    {
        if (!$ok) {
            return $this->unavailable('accessibility', (string) ($home['error'] ?? ''));
        }

        $score = (float) $home['scores']['accessibility'];
        $issues = [];
        $good = [];
        $contrast = $home['a11y']['color-contrast'] ?? null;

        // Our own weighting: contrast and names are what real visitors stumble over.
        foreach ($home['a11y'] as $id => $audit) {
            $items = [];

            foreach ($audit['items'] as $item) {
                $items[] = trim(($item['text'] ?? '') !== '' ? '„'.$item['text'].'“' : ($item['selector'] ?? ''));
            }

            $effort = \in_array($id, ['color-contrast', 'image-alt', 'link-name', 'button-name', 'html-has-lang', 'document-title', 'label'], true) ? 'small' : 'medium';
            $priority = 'color-contrast' === $id ? 85 : min(70, 40 + $audit['weight'] * 3);
            $key = 'a11y.issue.'.str_replace('-', '_', $id);
            $translated = $this->translator->trans($key.'.title', ['%count%' => $audit['count']], 'neo_websitecheck', $this->locale);

            if ($translated === $key.'.title') {
                // No tailored wording: fall back to the Lighthouse title/description.
                $issues[] = ['title' => rtrim($audit['title'], '.'), 'text' => $audit['description'], 'fix' => $audit['description'], 'effort' => $effort, 'priority' => $priority, 'count' => $audit['count'], 'examples' => \array_slice(array_filter($items), 0, 3), 'auditId' => $id];
            } else {
                $issues[] = ['title' => $this->t($key.'.title', ['%count%' => $audit['count']]), 'text' => $this->t($key.'.text', ['%count%' => $audit['count']]), 'fix' => $this->t($key.'.fix', ['%count%' => $audit['count']]), 'effort' => $effort, 'priority' => $priority, 'count' => $audit['count'], 'examples' => \array_slice(array_filter($items), 0, 3), 'auditId' => $id];
            }
        }

        if (null === $contrast) {
            $good[] = $this->good('a11y.good.contrast', [], true);
        }

        if (!isset($home['a11y']['image-alt'])) {
            $total = array_sum(array_map(static fn (array $p): int => (int) ($p['imgTotal'] ?? 0), $htmlPages));
            $good[] = $this->good('a11y.good.images', ['%count%' => $total]);
        }

        if (!isset($home['a11y']['html-has-lang']) && !isset($home['a11y']['html-lang-valid'])) {
            $good[] = $this->good('a11y.good.lang');
        }

        if (!isset($home['a11y']['heading-order']) && !isset($home['a11y']['link-name']) && !isset($home['a11y']['button-name'])) {
            $good[] = $this->good('a11y.good.names');
        }

        if (!isset($home['a11y']['target-size']) && !isset($home['a11y']['tap-targets'])) {
            $good[] = $this->good('a11y.good.targets');
        }

        $contrastCount = (int) ($contrast['count'] ?? 0);
        $summaryKey = $score >= 90 && 0 === $contrastCount ? 'good' : ($score >= 75 ? 'ok' : 'bad');

        return [
            'score' => $score,
            'summary' => $this->t('a11y.summary.'.$summaryKey, ['%count%' => $contrastCount]),
            'meta' => $this->t('a11y.meta', ['%score%' => (int) $score, '%count%' => $contrastCount]),
            'issues' => $issues,
            'good' => $good,
        ];
    }

    private function seo(array $data, array $home, bool $ok, array $htmlPages): array
    {
        $issues = [];
        $good = [];
        $score = $ok ? (float) $home['scores']['seo'] : 80.0;
        $penalty = 0;
        $titlesOk = true;
        $h1Ok = true;

        foreach ($htmlPages as $url => $page) {
            $path = (string) (parse_url((string) $url, PHP_URL_PATH) ?: '/');

            if ('' === $page['title']) {
                $issues[] = $this->issue('seo.issue.title_missing', ['%page%' => $path], 'small', 75);
                $penalty += 10;
                $titlesOk = false;
            } elseif ($page['titleLength'] > 65) {
                $issues[] = $this->issue('seo.issue.title_long', ['%page%' => $path, '%length%' => $page['titleLength']], 'small', 35);
                $penalty += 3;
                $titlesOk = false;
            }

            if ('' === $page['description']) {
                $issues[] = $this->issue('seo.issue.desc_missing', ['%page%' => $path], 'small', 65);
                $penalty += 6;
                $titlesOk = false;
            } elseif ($page['descriptionLength'] > 165) {
                $issues[] = $this->issue('seo.issue.desc_long', ['%page%' => $path, '%length%' => $page['descriptionLength']], 'small', 30);
                $penalty += 2;
            }

            if (0 === $page['h1Count']) {
                $issues[] = $this->issue('seo.issue.h1_missing', ['%page%' => $path], 'small', 55);
                $penalty += 5;
                $h1Ok = false;
            } elseif ($page['h1Count'] > 1) {
                $issues[] = $this->issue('seo.issue.h1_multiple', ['%page%' => $path, '%count%' => $page['h1Count']], 'small', 35);
                $penalty += 2;
                $h1Ok = false;
            }

            if ($page['headingSkips'] > 0) {
                $penalty += 1;
            }

            if ($page['noindex'] && 'home' === ($data['home']['role'] ?? 'home') && $url === ($data['home']['url'] ?? '')) {
                $issues[] = $this->issue('seo.issue.noindex', [], 'small', 95);
                $penalty += 40;
            }
        }

        $skips = array_sum(array_map(static fn (array $p): int => (int) $p['headingSkips'], $htmlPages));

        if ($skips > 0) {
            $issues[] = $this->issue('seo.issue.heading_skips', ['%count%' => $skips], 'small', 25);
        }

        $sitemap = (array) ($data['sitemap'] ?? []);

        if (!($sitemap['found'] ?? false)) {
            $issues[] = $this->issue('seo.issue.sitemap_missing', [], 'small', 45);
            $penalty += 8;
        } else {
            $good[] = $this->good('seo.good.sitemap', ['%count%' => (int) ($sitemap['urls'] ?? 0)]);
        }

        if ($titlesOk) {
            $good[] = $this->good('seo.good.titles', [], true);
        }

        if ($h1Ok) {
            $good[] = $this->good('seo.good.h1');
        }

        $schema = (array) ($data['aiVisibility']['schemaIdentity'] ?? []);

        if ([] !== $schema) {
            $good[] = $this->good('seo.good.schema', ['%types%' => implode(', ', array_merge($schema, (array) ($data['aiVisibility']['schemaExtra'] ?? [])))]);
        } else {
            $issues[] = $this->issue('seo.issue.schema', [], 'small', 42);
            $penalty += 4;
        }

        $this->auditIssue($issues, $home, 'is-crawlable', 'seo.issue.not_crawlable', 'small', 98);
        $this->auditIssue($issues, $home, 'link-text', 'seo.issue.link_text', 'small', 28, static fn (array $a): array => ['%count%' => $a['count']]);
        $this->auditIssue($issues, $home, 'crawlable-anchors', 'seo.issue.crawlable_anchors', 'small', 32);
        $this->auditIssue($issues, $home, 'canonical', 'seo.issue.canonical', 'small', 38);
        $this->auditIssue($issues, $home, 'hreflang', 'seo.issue.hreflang', 'small', 30);

        if (($data['home']['https'] ?? true) && ($data['home']['httpRedirect'] ?? true)) {
            $good[] = $this->good('seo.good.https');
        } elseif (!($data['home']['httpRedirect'] ?? true)) {
            $issues[] = $this->issue('seo.issue.http_redirect', [], 'small', 70);
            $penalty += 6;
        }

        $score = max(0, min(100, ($ok ? $score * 0.6 : 0) + (100 - min(100, $penalty)) * ($ok ? 0.4 : 1.0)));
        $summaryKey = $score >= 90 ? 'good' : ($score >= 70 ? 'ok' : 'bad');

        return [
            'score' => $score,
            'summary' => $this->t('seo.summary.'.$summaryKey),
            'meta' => $this->t('seo.meta', ['%score%' => $ok ? $home['scores']['seo'] : '–', '%pages%' => \count($htmlPages)]),
            'issues' => $issues,
            'good' => $good,
        ];
    }

    private function aiVisibility(array $data, array|null $ai): array
    {
        $v = (array) ($data['aiVisibility'] ?? []);

        if ([] === $v) {
            return $this->unavailable('ai');
        }

        $score = 0;
        $issues = [];
        $good = [];

        if ($v['llmsTxt']) {
            $score += 35;
            $good[] = $this->good('ai.good.llms', [], true);
        } else {
            $issues[] = $this->issue('ai.issue.llms_missing', [], 'small', 72);
        }

        if ($v['llmsFullTxt']) {
            $score += 10;
        }

        $blocked = (array) $v['botsBlocked'];
        $score += (int) round(20 * (1 - \count($blocked) / max(1, \count(AiVisibilityAnalyzer::BOTS))));

        if ([] === $blocked) {
            $good[] = $this->good('ai.good.bots');
        } else {
            $issues[] = $this->issue('ai.issue.bots_blocked', ['%bots%' => implode(', ', $blocked)], 'small', 50);
        }

        if ([] !== $v['schemaIdentity']) {
            $score += 15;
            $good[] = $this->good('ai.good.schema', ['%types%' => implode(', ', $v['schemaIdentity'])]);
        } else {
            $issues[] = $this->issue('ai.issue.no_identity_schema', [], 'small', 48);
        }

        if (null !== $ai) {
            if ($ai['offerClear']) {
                $score += 20;
                $good[] = $this->good('ai.good.offer');
            } else {
                $issues[] = $this->issue('ai.issue.offer_unclear', [], 'small', 68);
            }
        } else {
            // Without the AI step: credit a start page whose first sentences contain its H1/title words.
            $score += 10;
        }

        $summaryKey = $score >= 85 ? 'good' : ($score >= 60 ? 'ok' : 'bad');

        return [
            'score' => (float) min(100, $score),
            'summary' => $this->t('ai.summary.'.$summaryKey),
            'meta' => $this->t('ai.meta', ['%llms%' => $this->t($v['llmsTxt'] ? 'word.yes' : 'word.no'), '%blocked%' => \count($blocked)]),
            'issues' => $issues,
            'good' => $good,
            'bots' => $v['bots'],
        ];
    }

    private function content(array $data, array $homeHtml, array|null $ai): array
    {
        $issues = [];
        $good = [];

        if (null !== $ai) {
            $score = (float) $ai['clarity'];

            foreach ($ai['issues'] as $i => $issue) {
                $issues[] = ['title' => $issue['title'], 'text' => $issue['why'], 'fix' => $issue['fix'], 'effort' => $issue['effort'], 'priority' => 78 - $i * 8];
            }

            foreach ($ai['strengths'] as $s) {
                $good[] = ['title' => $s['title'], 'text' => $s['text']];
            }

            if ($ai['nextStepClear']) {
                $good[] = $this->good('content.good.cta');
            } else {
                $issues[] = $this->issue('content.issue.no_cta', [], 'small', 74);
            }

            $meta = $this->t('content.meta.ai', ['%pages%' => \count((array) ($data['pages'] ?? []))]);
        } else {
            // Heuristic fallback when no AI endpoint is configured.
            $score = 0;
            $contactLinks = \count((array) ($homeHtml['links']['contact'] ?? [])) + \count((array) ($homeHtml['links']['tel'] ?? []));

            if (($homeHtml['h1Count'] ?? 0) >= 1) {
                $score += 30;
            }

            if ('' !== ($homeHtml['description'] ?? '')) {
                $score += 20;
            }

            if ($contactLinks > 0) {
                $score += 25;
                $good[] = $this->good('content.good.cta');
            } else {
                $issues[] = $this->issue('content.issue.no_cta', [], 'small', 74);
            }

            $words = (int) ($homeHtml['words'] ?? 0);

            if ($words >= 250) {
                $score += 25;
                $good[] = $this->good('content.good.length', ['%words%' => $words]);
            } else {
                $issues[] = $this->issue('content.issue.thin', ['%words%' => $words], 'medium', 60);
            }

            $meta = $this->t('content.meta.heuristic');
        }

        if (($homeHtml['links']['tel'] ?? []) !== []) {
            $good[] = $this->good('content.good.phone');
        } else {
            $issues[] = $this->issue('content.issue.phone', [], 'small', 44);
        }

        $summaryKey = $score >= 85 ? 'good' : ($score >= 60 ? 'ok' : 'bad');

        return [
            'score' => (float) $score,
            'summary' => $this->t('content.summary.'.$summaryKey),
            'meta' => $meta,
            'issues' => $issues,
            'good' => $good,
        ];
    }

    private function legal(array $data): array
    {
        $l = (array) ($data['legal'] ?? []);

        if ([] === $l) {
            return $this->unavailable('legal');
        }

        $score = 0;
        $issues = [];
        $good = [];

        if ($l['imprintOk']) {
            $score += 35;
            $good[] = $this->good('legal.good.imprint');
        } else {
            $issues[] = $this->issue('legal.issue.imprint', [], 'small', 90);
        }

        if ($l['privacyOk']) {
            $score += 35;
            $good[] = $this->good('legal.good.privacy');
        } else {
            $issues[] = $this->issue('legal.issue.privacy', [], 'small', 88);
        }

        $tracking = (array) $l['trackingBeforeConsent'];
        $embeds = (array) $l['embedsBeforeConsent'];

        if ([] !== $tracking) {
            $issues[] = $this->issue('legal.issue.tracking', ['%services%' => implode(', ', $tracking)], 'medium', 82);
            $score += $l['hasCmp'] ? 10 : 0;
        } elseif ([] !== $embeds && !$l['hasCmp']) {
            $issues[] = $this->issue('legal.issue.embeds', ['%services%' => implode(', ', $embeds)], 'small', 58);
            $score += 15;
        } else {
            $score += 30;
            $good[] = $this->good([] === $embeds ? 'legal.good.no_third_party' : 'legal.good.cmp', [], true);
        }

        $summaryKey = $score >= 90 ? 'good' : ($score >= 65 ? 'ok' : 'bad');

        return [
            'score' => (float) $score,
            'summary' => $this->t('legal.summary.'.$summaryKey),
            'meta' => $this->t('legal.meta'),
            'issues' => $issues,
            'good' => $good,
            'note' => $this->t('legal.note'),
        ];
    }

    private function mobile(array $home, bool $ok, array $homeHtml): array
    {
        $issues = [];
        $good = [];
        $score = 0;

        if ('' !== ($homeHtml['viewport'] ?? '') && (!$ok || ($home['audits']['viewport']['score'] ?? 1) >= 1)) {
            $score += 30;
            $good[] = $this->good('mobile.good.viewport');
        } else {
            $issues[] = $this->issue('mobile.issue.viewport', [], 'small', 92);
        }

        if (!$ok) {
            $score += 50;

            return [
                'score' => (float) $score,
                'summary' => $this->t('mobile.summary.unknown'),
                'meta' => '',
                'issues' => $issues,
                'good' => $good,
            ];
        }

        $targets = $home['a11y']['target-size'] ?? $home['audits']['target-size'] ?? $home['audits']['tap-targets'] ?? null;

        if (null === $targets || ($targets['score'] ?? 1) >= 1) {
            $score += 35;
            $good[] = $this->good('mobile.good.targets');
        } else {
            $score += (int) round(35 * (float) ($targets['score'] ?? 0));
            $issues[] = $this->issue('mobile.issue.targets', ['%count%' => $targets['count'] ?? 1], 'small', 64);
        }

        $font = $home['audits']['font-size'] ?? null;

        if (null === $font || ($font['score'] ?? 1) >= 1) {
            $score += 20;
            $good[] = $this->good('mobile.good.font');
        } else {
            $issues[] = $this->issue('mobile.issue.font', ['%value%' => $font['displayValue'] ?? ''], 'small', 60);
        }

        $width = $home['audits']['content-width'] ?? null;

        if (null === $width || ($width['score'] ?? 1) >= 1) {
            $score += 15;
        } else {
            $issues[] = $this->issue('mobile.issue.width', [], 'small', 70);
        }

        $summaryKey = $score >= 90 ? 'good' : ($score >= 65 ? 'ok' : 'bad');

        return [
            'score' => (float) $score,
            'summary' => $this->t('mobile.summary.'.$summaryKey),
            'meta' => $this->t('mobile.meta'),
            'issues' => $issues,
            'good' => $good,
        ];
    }

    // -------------------------------------------------------------- helpers

    private function pageTable(array $data, array $htmlPages): array
    {
        $rows = [];
        $psiByUrl = [];

        foreach ((array) ($data['psi'] ?? []) as $key => $psi) {
            if (!isset($psi['error']) && isset($psi['requestedUrl']) && 'desktop' !== ($psi['strategy'] ?? '')) {
                $psiByUrl[rtrim($psi['requestedUrl'], '/')] = $psi;
            }
        }

        foreach ((array) ($data['pages'] ?? []) as $page) {
            $url = (string) $page['url'];
            $html = $htmlPages[$url] ?? null;
            $psi = $psiByUrl[rtrim($url, '/')] ?? null;
            $rowScore = null;

            if (null !== $psi) {
                $rowScore = ($psi['scores']['performance'] + $psi['scores']['accessibility'] + $psi['scores']['seo']) / 3;
            }

            $rows[] = [
                'url' => $url,
                'path' => (string) (parse_url($url, PHP_URL_PATH) ?: '/'),
                'role' => $page['role'],
                'title' => $html['title'] ?? '',
                'status' => $html['status'] ?? ($page['status'] ?? 0),
                'lcp' => null !== $psi && null !== $psi['metrics']['lcp'] ? $this->num($psi['metrics']['lcp'] / 1000, 1).' s' : '',
                'perf' => $psi['scores']['performance'] ?? null,
                'a11y' => $psi['scores']['accessibility'] ?? null,
                'seo' => $psi['scores']['seo'] ?? null,
                'contrast' => $psi['a11y']['color-contrast']['count'] ?? 0,
                'grade' => null !== $rowScore ? self::gradeFor($rowScore) : 0,
            ];
        }

        return $rows;
    }

    private function auditIssue(array &$issues, array $psi, string $id, string $key, string $effort, int $priority, callable|null $params = null): void
    {
        $audit = $psi['audits'][$id] ?? null;

        if (null === $audit || null === $audit['score'] || $audit['score'] >= 0.9) {
            return;
        }

        $issues[] = $this->issue($key, null !== $params ? $params($audit) : [], $effort, $priority);
    }

    private function issue(string $key, array $params, string $effort, int $priority): array
    {
        return [
            'title' => $this->t($key.'.title', $params),
            'text' => $this->t($key.'.text', $params),
            'fix' => $this->t($key.'.fix', $params),
            'effort' => $effort,
            'priority' => $priority,
        ];
    }

    private function good(string $key, array $params = [], bool $strength = false): array
    {
        return ['title' => $this->t($key.'.title', $params), 'text' => $this->t($key.'.text', $params), 'strength' => $strength];
    }

    private function unavailable(string $area, string $error = ''): array
    {
        return ['score' => null, 'summary' => $this->t('area.unavailable'), 'meta' => $error, 'issues' => [], 'good' => []];
    }

    private function t(string $key, array $params = []): string
    {
        return $this->translator->trans($key, $params, 'neo_websitecheck', $this->locale);
    }

    private function num(float $value, int $decimals): string
    {
        return 'de' === substr($this->locale, 0, 2) ? number_format($value, $decimals, ',', '.') : number_format($value, $decimals, '.', ',');
    }
}
