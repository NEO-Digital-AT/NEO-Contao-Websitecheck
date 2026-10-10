<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Check;

use Neo\WebsitecheckBundle\Settings;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Google PageSpeed Insights v5 (Lighthouse as a service). One call returns the
 * four category scores, lab metrics, field data (CrUX) when available, the
 * failing audits with their DOM nodes, a final screenshot and a full-page
 * screenshot with node rectangles — everything the report needs, no local
 * Chrome required. Several URLs are measured concurrently.
 */
final class PageSpeedClient
{
    private const ENDPOINT = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    /** Audits whose outcome the report uses beyond the accessibility category. */
    private const AUDITS = [
        'viewport', 'target-size', 'tap-targets', 'font-size', 'content-width',
        'document-title', 'meta-description', 'is-crawlable', 'robots-txt', 'canonical', 'hreflang', 'http-status-code', 'link-text', 'crawlable-anchors', 'structured-data',
        'is-on-https', 'redirects-http', 'errors-in-console', 'deprecations', 'third-party-cookies',
        'render-blocking-resources', 'uses-responsive-images', 'modern-image-formats', 'uses-optimized-images', 'unused-javascript', 'unused-css-rules', 'uses-text-compression', 'server-response-time', 'largest-contentful-paint-element', 'total-byte-weight', 'font-display', 'uses-long-cache-ttl',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Settings $settings,
    ) {
    }

    public function isConfigured(): bool
    {
        return '' !== $this->settings->pageSpeedKey();
    }

    /**
     * Runs several measurements concurrently.
     *
     * @param array<string, array{url: string, strategy: string}> $jobs keyed by a caller-chosen name
     *
     * @return array<string, array<string, mixed>> same keys; each value has 'error' set when the call failed
     */
    public function runMany(array $jobs, string $locale): array
    {
        $responses = [];

        foreach ($jobs as $key => $job) {
            $query = [
                'url' => $job['url'],
                'strategy' => $job['strategy'],
                'locale' => substr($locale, 0, 2),
                'category' => ['performance', 'accessibility', 'best-practices', 'seo'],
            ];

            if ($this->isConfigured()) {
                $query['key'] = $this->settings->pageSpeedKey();
            }

            try {
                $responses[$key] = $this->httpClient->request('GET', self::ENDPOINT, [
                    'query' => $query,
                    'timeout' => $this->settings->pageSpeedTimeout(),
                    'max_duration' => $this->settings->pageSpeedTimeout() + 10,
                ]);
            } catch (\Throwable $e) {
                $responses[$key] = $e;
            }
        }

        $out = [];

        foreach ($responses as $key => $response) {
            if ($response instanceof \Throwable) {
                $out[$key] = ['error' => $response->getMessage(), 'url' => $jobs[$key]['url'], 'strategy' => $jobs[$key]['strategy']];
                continue;
            }

            try {
                $status = $response->getStatusCode();
                $json = $response->toArray(false);

                if ($status >= 400 || !isset($json['lighthouseResult'])) {
                    $message = (string) ($json['error']['message'] ?? ('HTTP '.$status));
                    $out[$key] = ['error' => $message, 'url' => $jobs[$key]['url'], 'strategy' => $jobs[$key]['strategy']];
                    continue;
                }

                $out[$key] = $this->extract($json, $jobs[$key]['strategy']);
            } catch (\Throwable $e) {
                $out[$key] = ['error' => $e->getMessage(), 'url' => $jobs[$key]['url'], 'strategy' => $jobs[$key]['strategy']];
            }
        }

        return $out;
    }

    /**
     * Reduces the (multi-megabyte) Lighthouse JSON to what the check needs.
     * Screenshots stay as data URLs under "screenshots" and must be written to
     * disk by the caller (ScreenshotStore), never stored in the database.
     *
     * @param array<string, mixed> $json
     *
     * @return array<string, mixed>
     */
    private function extract(array $json, string $strategy): array
    {
        $lr = $json['lighthouseResult'];
        $audits = $lr['audits'] ?? [];
        $scores = [];

        foreach (['performance', 'accessibility', 'best-practices', 'seo'] as $cat) {
            $score = $lr['categories'][$cat]['score'] ?? null;
            $scores[$cat] = null === $score ? null : (int) round($score * 100);
        }

        $num = static fn (string $id): float|null => isset($audits[$id]['numericValue']) ? (float) $audits[$id]['numericValue'] : null;

        $metrics = [
            'lcp' => $num('largest-contentful-paint'),
            'cls' => $num('cumulative-layout-shift'),
            'tbt' => $num('total-blocking-time'),
            'fcp' => $num('first-contentful-paint'),
            'si' => $num('speed-index'),
            'ttfb' => $num('server-response-time'),
            'bytes' => $num('total-byte-weight'),
        ];

        $field = [];

        foreach (['LARGEST_CONTENTFUL_PAINT_MS' => 'lcp', 'CUMULATIVE_LAYOUT_SHIFT_SCORE' => 'cls', 'INTERACTION_TO_NEXT_PAINT' => 'inp'] as $key => $name) {
            if (isset($json['loadingExperience']['metrics'][$key]['percentile']) && empty($json['loadingExperience']['origin_fallback'])) {
                $field[$name] = $json['loadingExperience']['metrics'][$key]['percentile'];
            }
        }

        // Network: third parties, request count, largest image.
        $network = ['requests' => 0, 'thirdParties' => [], 'largestImage' => null, 'images' => 0, 'hosts' => []];
        $pageHost = strtolower((string) parse_url((string) ($lr['finalDisplayedUrl'] ?? $lr['finalUrl'] ?? ''), PHP_URL_HOST));
        $pageHost = preg_replace('/^www\./', '', $pageHost) ?? $pageHost;

        foreach ((array) ($audits['network-requests']['details']['items'] ?? []) as $item) {
            ++$network['requests'];
            $host = strtolower((string) parse_url((string) ($item['url'] ?? ''), PHP_URL_HOST));

            if ('' === $host) {
                continue;
            }

            $bare = preg_replace('/^www\./', '', $host) ?? $host;
            $network['hosts'][$bare] = true;

            if ($bare !== $pageHost && !str_ends_with($bare, '.'.$pageHost)) {
                $network['thirdParties'][$bare] = ($network['thirdParties'][$bare] ?? 0) + (int) ($item['transferSize'] ?? 0);
            }

            if ('Image' === ($item['resourceType'] ?? '')) {
                ++$network['images'];
                $size = (int) ($item['transferSize'] ?? 0);

                if (null === $network['largestImage'] || $size > $network['largestImage']['bytes']) {
                    $network['largestImage'] = ['url' => (string) $item['url'], 'bytes' => $size];
                }
            }
        }

        arsort($network['thirdParties']);
        $network['hosts'] = array_keys($network['hosts']);

        // Compact audit outcomes (score + display value + a few items).
        $selected = [];

        foreach (self::AUDITS as $id) {
            if (!isset($audits[$id])) {
                continue;
            }

            $selected[$id] = $this->compactAudit($audits[$id], 3);
        }

        // Every failing accessibility audit with up to five offending nodes.
        $a11y = [];

        foreach ((array) ($lr['categories']['accessibility']['auditRefs'] ?? []) as $ref) {
            $id = (string) ($ref['id'] ?? '');
            $audit = $audits[$id] ?? null;

            if (null === $audit || !isset($audit['score']) || (float) $audit['score'] >= 1) {
                continue;
            }

            $a11y[$id] = $this->compactAudit($audit, 5) + ['weight' => (int) ($ref['weight'] ?? 0)];
        }

        uasort($a11y, static fn (array $a, array $b): int => $b['weight'] <=> $a['weight']);

        // Lighthouse ≥ 10 reports the full-page screenshot at the top level,
        // older versions as an audit.
        $full = $lr['fullPageScreenshot'] ?? $audits['full-page-screenshot']['details'] ?? null;

        return [
            'url' => (string) ($lr['finalDisplayedUrl'] ?? $lr['finalUrl'] ?? ($lr['requestedUrl'] ?? '')),
            'requestedUrl' => (string) ($lr['requestedUrl'] ?? ''),
            'strategy' => $strategy,
            'fetchedAt' => (string) ($lr['fetchTime'] ?? ''),
            'version' => (string) ($lr['lighthouseVersion'] ?? ''),
            'scores' => $scores,
            'metrics' => $metrics,
            'field' => $field,
            'network' => $network,
            'audits' => $selected,
            'a11y' => $a11y,
            'screenshots' => [
                'final' => (string) ($audits['final-screenshot']['details']['data'] ?? ''),
                'full' => \is_array($full) ? [
                    'data' => (string) ($full['screenshot']['data'] ?? ''),
                    'width' => (int) ($full['screenshot']['width'] ?? 0),
                    'height' => (int) ($full['screenshot']['height'] ?? 0),
                ] : null,
                'nodes' => \is_array($full) ? (array) ($full['nodes'] ?? []) : [],
                // emulated screen (CSS px) to cut the first screen out of the full-page capture
                'viewport' => [
                    'width' => (int) ($lr['configSettings']['screenEmulation']['width'] ?? ('desktop' === $strategy ? 1350 : 412)),
                    'height' => (int) ($lr['configSettings']['screenEmulation']['height'] ?? ('desktop' === $strategy ? 940 : 823)),
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $audit
     *
     * @return array{score: float|null, title: string, description: string, displayValue: string, items: list<array<string, mixed>>, count: int}
     */
    /** Lighthouse texts carry markdown links and "[Learn more]" tails that mean nothing in a report. */
    private function plain(string $text): string
    {
        $text = preg_replace('/\[([^\]]*)\]\([^)]*\)/u', '$1', $text) ?? $text;
        $text = preg_replace('/\s*\((Learn more|Weitere Informationen)[^)]*\)\.?/iu', '', $text) ?? $text;

        return trim(strip_tags($text));
    }

    private function compactAudit(array $audit, int $maxItems): array
    {
        $items = [];
        $all = (array) ($audit['details']['items'] ?? []);

        foreach (\array_slice($all, 0, $maxItems) as $item) {
            $node = $item['node'] ?? null;
            $compact = [];

            foreach (['url', 'displayValue', 'wastedMs', 'wastedBytes', 'totalBytes', 'transferSize', 'label', 'entity'] as $k) {
                if (isset($item[$k]) && \is_scalar($item[$k])) {
                    $compact[$k] = $item[$k];
                }
            }

            if (\is_array($node)) {
                $compact['selector'] = (string) ($node['selector'] ?? '');
                $compact['snippet'] = mb_substr((string) ($node['snippet'] ?? ''), 0, 300);
                $compact['text'] = mb_substr((string) ($node['nodeLabel'] ?? ''), 0, 120);
                $compact['explanation'] = mb_substr($this->plain((string) ($node['explanation'] ?? '')), 0, 400);
                $compact['lhId'] = (string) ($node['lhId'] ?? '');
                $compact['rect'] = isset($node['boundingRect']) ? array_map('intval', array_intersect_key($node['boundingRect'], array_flip(['top', 'left', 'width', 'height']))) : null;
            }

            $items[] = $compact;
        }

        return [
            'score' => isset($audit['score']) ? (float) $audit['score'] : null,
            'title' => (string) ($audit['title'] ?? ''),
            'description' => mb_substr($this->plain((string) ($audit['description'] ?? '')), 0, 220),
            'displayValue' => (string) ($audit['displayValue'] ?? ''),
            'items' => $items,
            'count' => \count($all),
        ];
    }

    /**
     * An icon-font glyph (Material Icons/Symbols, Font Awesome …): Lighthouse
     * reports its ligature text ("cleaning_services", "search"), and Google's
     * screenshot often shows that text because the icon font was not loaded
     * yet. A marked excerpt of it would show a word the visitor never sees.
     *
     * @param array<string, mixed> $item
     */
    public static function isIconGlyph(array $item): bool
    {
        $text = trim((string) ($item['text'] ?? ''));
        $snippet = (string) ($item['snippet'] ?? '');

        if (preg_match('/material-(icons|symbols)|\bfa-|\b(icon|icons|ico|glyph|symbol)\b/i', $snippet) && preg_match('/^[a-z0-9_]+$/', $text)) {
            return true;
        }

        // an icon button: its only text is one lower-case word, the accessible name comes from aria-label
        if (preg_match('/^[a-z]+$/', $text) && preg_match('/aria-label="[^"]+"/i', $snippet)) {
            return true;
        }

        // ligature names: lower case words joined by underscores ("arrow_forward")
        return 1 === preg_match('/^[a-z]+(_[a-z]+)+$/', $text);
    }
}
