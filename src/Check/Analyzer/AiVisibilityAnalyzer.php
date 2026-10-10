<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Check\Analyzer;

/**
 * Generative-engine visibility: llms.txt / llms-full.txt present, robots.txt
 * rules for the AI crawlers, structured data that lets an assistant identify
 * the business, and whether the offer is stated in the first sentences.
 */
final class AiVisibilityAnalyzer
{
    public const BOTS = ['GPTBot', 'ClaudeBot', 'PerplexityBot', 'Google-Extended', 'CCBot', 'Applebot-Extended'];

    /**
     * @param array{status: int, body: string} $robots
     * @param array{status: int, body: string, headers: array<string, list<string>>} $llms
     * @param array{status: int, body: string, headers: array<string, list<string>>} $llmsFull
     * @param array<string, mixed> $home HtmlAnalyzer result of the start page
     *
     * @return array<string, mixed>
     */
    public function analyze(array $robots, array $llms, array $llmsFull, array $home): array
    {
        $rules = $this->parseRobots($robots['status'] < 400 ? $robots['body'] : '');
        $bots = [];

        foreach (self::BOTS as $bot) {
            $bots[$bot] = $this->allowed($rules, $bot);
        }

        $schema = (array) ($home['schema'] ?? []);
        $identity = array_values(array_intersect($schema, ['Organization', 'LocalBusiness', 'Corporation', 'ProfessionalService', 'Store', 'Restaurant', 'Hotel', 'LodgingBusiness', 'MedicalBusiness', 'Dentist', 'Physician', 'LegalService', 'Person', 'WebSite']));
        $extra = array_values(array_intersect($schema, ['FAQPage', 'Service', 'Product', 'Offer', 'Event', 'Article', 'BlogPosting', 'BreadcrumbList']));

        return [
            'llmsTxt' => $this->isTextFile($llms),
            'llmsFullTxt' => $this->isTextFile($llmsFull),
            'llmsTxtExcerpt' => $this->isTextFile($llms) ? mb_substr(trim($llms['body']), 0, 400) : '',
            'robotsFound' => $robots['status'] < 400 && '' !== trim($robots['body']),
            'bots' => $bots,
            'botsBlocked' => array_keys(array_filter($bots, static fn (bool $ok): bool => !$ok)),
            'sitemapInRobots' => (bool) preg_match('/^\s*sitemap\s*:/im', (string) $robots['body']),
            'schemaIdentity' => $identity,
            'schemaExtra' => $extra,
            'firstSentences' => mb_substr(trim((string) ($home['text'] ?? '')), 0, 320),
        ];
    }

    private function isTextFile(array $response): bool
    {
        if (($response['status'] ?? 0) >= 400 || '' === trim((string) ($response['body'] ?? ''))) {
            return false;
        }

        $body = ltrim((string) $response['body']);

        // A soft-404 HTML page is not an llms.txt.
        return !str_starts_with(strtolower($body), '<!doctype') && !str_starts_with(strtolower($body), '<html') && !str_contains(substr($body, 0, 200), '<head');
    }

    /**
     * @return array<string, array{allow: list<string>, disallow: list<string>}>
     */
    private function parseRobots(string $body): array
    {
        $rules = [];
        $agents = [];

        foreach (preg_split('/\r?\n/', $body) ?: [] as $line) {
            $line = trim(preg_replace('/#.*$/', '', $line) ?? '');

            if ('' === $line || !str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode(':', $line, 2));
            $key = strtolower($key);

            if ('user-agent' === $key) {
                $agents[] = strtolower($value);
                $rules[strtolower($value)] ??= ['allow' => [], 'disallow' => []];
                continue;
            }

            if (\in_array($key, ['allow', 'disallow'], true)) {
                foreach ($agents as $agent) {
                    $rules[$agent][$key][] = $value;
                }
                continue;
            }

            // Any other directive ends the current user-agent group.
            if (!\in_array($key, ['crawl-delay', 'sitemap', 'host'], true)) {
                $agents = [];
            }

            if ('sitemap' === $key) {
                // keep the group open — sitemap lines may appear anywhere
            }
        }

        return $rules;
    }

    /** True when the bot may fetch "/" (group for the bot, else "*"). */
    private function allowed(array $rules, string $bot): bool
    {
        $group = $rules[strtolower($bot)] ?? $rules['*'] ?? null;

        if (null === $group) {
            return true;
        }

        $disallowRoot = \in_array('/', $group['disallow'], true);
        $allowRoot = \in_array('/', $group['allow'], true);

        return !$disallowRoot || $allowRoot;
    }
}
