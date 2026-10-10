<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Check;

use Neo\WebsitecheckBundle\Settings;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * "Reads the site like a visitor": one call to an OpenAI-compatible
 * chat-completions endpoint (Requesty, OpenAI, Azure, Ollama, …) with the
 * visible text of the checked pages and, for vision models, the mobile
 * screenshot of the start page. The model must answer with a fixed JSON
 * shape; it judges clarity and copy only — all technical grades come from
 * measurements, and the prompt forbids inventing problems.
 */
final class AiReviewer
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Settings $settings,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->settings->aiEnabled();
    }

    /**
     * @param list<array{url: string, role: string, title: string, text: string}> $pages
     * @param list<array<string, mixed>>                                         $findings marked excerpts cut from Google's measurement (ref, title, text, explanation)
     *
     * @return array<string, mixed>|null null when disabled or the answer was unusable
     */
    public function review(string $domain, array $pages, string|null $screenshotPath, string $locale, array $findings = []): array|null
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $ai = $this->settings->ai();
        $language = 'de' === substr($locale, 0, 2) ? 'German (Sie-Form, Austrian/German business tone)' : 'English';

        $corpus = '';

        foreach ($pages as $i => $page) {
            $limit = 0 === $i ? 7000 : 3500;
            $corpus .= "\n\n=== PAGE ".($i + 1).' ('.$page['role'].') '.$page['url']."\nTITLE: ".$page['title']."\n".mb_substr($page['text'], 0, $limit);
        }

        // Measured findings with a marked screenshot excerpt: the AI chooses which ones the report shows.
        $findingList = '';

        foreach ($findings as $finding) {
            $findingList .= sprintf(
                "\n- ref %s: %s | visible text: \"%s\"%s",
                (string) ($finding['ref'] ?? ''),
                (string) ($finding['title'] ?? ''),
                mb_substr((string) ($finding['text'] ?? ''), 0, 120),
                isset($finding['ratio']) ? ' | contrast '.$finding['ratio'].':1 (needs 4.5:1)' : '',
            );
        }

        $areas = 'loading speed, accessibility, findability on Google, visibility in AI search, content & clarity, legal & privacy, mobile';

        $system = <<<PROMPT
            You review small-business websites the way a first-time visitor experiences them. You receive the visible text of up to five pages and, if available, a screenshot of the start page on a phone.
            Judge ONLY content and clarity: Is it clear within five seconds what the business offers, for whom and where? Is there an obvious next step (call, request, booking)? Does the copy address the customer or talk about the company? Are technical terms explained? Is there anything that costs the business inquiries?
            Rules:
            - Be honest and specific. If the site does this well, say so — never invent problems. Praise what deserves praise.
            - Do not comment on loading speed, code, accessibility scores, legal pages or SEO tags; those are measured separately.
            - Write for a business owner without IT knowledge: short sentences, no jargon, no marketing fluff. Each issue explains why it costs customers and what to do.
            - Language of all texts: {$language}.
            Answer with JSON only, exactly this shape:
            {"first_impression": string (1-2 sentences, what a visitor understands after five seconds),
             "clarity": integer 0-100 (how clearly offer, audience, location and next step come across),
             "offer_clear": boolean, "next_step_clear": boolean, "customer_focused": boolean,
             "strengths": [{"title": string, "text": string}] (2-4 items),
             "issues": [{"title": string, "why": string, "fix": string, "effort": "small"|"medium"|"large"}] (0-5 items, most important first; empty if nothing matters),
             "summary": string (2-3 sentences for the cover page, honest, no sales pressure),
             "highlights": [{"ref": string, "caption": string}] (0-3 items, see below),
             "extra_sections": [{"title": string, "intro": string, "points": [{"title": string, "text": string, "kind": "good"|"warn"}]}] (0-2 items, see below)}
            highlights: The user message may list measured findings, each with a ref and a marked screenshot excerpt that the report shows. Pick two or three of them (all of them if fewer are listed), the ones a visitor of THIS business would notice or stumble over most, most important first. For each write one plain sentence: what the visitor sees at that spot and why it matters for this business. Use only refs from the list; an empty list only when no findings are listed.
            extra_sections: The report already covers these areas: {$areas}. Look for one or two further topics that matter for this particular kind of business and that none of those areas covers, for example how prices or offers come about, booking or ordering, trust (references, reviews, certificates, team photos), service area, opening hours or emergency service, languages. Each section: a short title, 1-2 sentences of intro, 2-4 points mixing what works ("good") and what is missing or unclear ("warn"). Base every point on what the page texts actually show or clearly lack; never repeat the issues or strengths above. Return an empty list only if there is truly nothing worth adding.
            PROMPT;

        $userContent = [['type' => 'text', 'text' => 'Website: '.$domain.('' !== $findingList ? "\n\nMEASURED FINDINGS WITH SCREENSHOT EXCERPT:".$findingList : '').$corpus]];

        if ($ai['send_screenshot'] && null !== $screenshotPath && is_file($screenshotPath)) {
            $userContent[] = [
                'type' => 'image_url',
                'image_url' => ['url' => 'data:image/jpeg;base64,'.base64_encode((string) file_get_contents($screenshotPath)), 'detail' => 'low'],
            ];
        }

        $payload = [
            'model' => $ai['model'],
            'temperature' => 0.3,
            'max_tokens' => $ai['max_tokens'],
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $userContent],
            ],
            'response_format' => ['type' => 'json_object'],
        ];

        try {
            $response = $this->httpClient->request('POST', rtrim($ai['base_url'], '/').'/chat/completions', [
                'headers' => ['Authorization' => 'Bearer '.$ai['api_key'], 'Content-Type' => 'application/json'],
                'json' => $payload,
                'timeout' => $ai['timeout'],
                'max_duration' => $ai['timeout'] + 10,
            ]);

            $json = $response->toArray(false);
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }

        if (isset($json['error'])) {
            // Some routers reject response_format: retry once without it.
            unset($payload['response_format']);

            try {
                $json = $this->httpClient->request('POST', rtrim($ai['base_url'], '/').'/chat/completions', [
                    'headers' => ['Authorization' => 'Bearer '.$ai['api_key'], 'Content-Type' => 'application/json'],
                    'json' => $payload,
                    'timeout' => $ai['timeout'],
                ])->toArray(false);
            } catch (\Throwable $e) {
                return ['error' => $e->getMessage()];
            }

            if (isset($json['error'])) {
                return ['error' => (string) ($json['error']['message'] ?? 'AI error')];
            }
        }

        $content = $json['choices'][0]['message']['content'] ?? '';

        if (\is_array($content)) {
            $content = implode('', array_map(static fn ($part) => \is_array($part) ? (string) ($part['text'] ?? '') : (string) $part, $content));
        }

        $parsed = $this->parseJson((string) $content);

        if (null === $parsed) {
            return ['error' => 'unparseable answer', 'raw' => mb_substr((string) $content, 0, 500)];
        }

        return [
            'firstImpression' => trim((string) ($parsed['first_impression'] ?? '')),
            'clarity' => max(0, min(100, (int) ($parsed['clarity'] ?? 0))),
            'offerClear' => (bool) ($parsed['offer_clear'] ?? false),
            'nextStepClear' => (bool) ($parsed['next_step_clear'] ?? false),
            'customerFocused' => (bool) ($parsed['customer_focused'] ?? false),
            'strengths' => $this->items($parsed['strengths'] ?? [], ['title', 'text']),
            'issues' => $this->items($parsed['issues'] ?? [], ['title', 'why', 'fix', 'effort']),
            'summary' => trim((string) ($parsed['summary'] ?? '')),
            'highlights' => $this->highlights($parsed['highlights'] ?? [], $findings),
            'sections' => $this->sections($parsed['extra_sections'] ?? []),
            'model' => (string) ($json['model'] ?? $ai['model']),
            'usage' => $json['usage'] ?? null,
        ];
    }

    /**
     * Picks of the model, restricted to refs that exist (no invented excerpts).
     *
     * @param list<array<string, mixed>> $findings
     *
     * @return list<array{ref: string, caption: string}>
     */
    private function highlights(mixed $list, array $findings): array
    {
        $refs = array_map(static fn (array $f): string => (string) ($f['ref'] ?? ''), $findings);
        $out = [];

        foreach ((array) $list as $item) {
            $ref = \is_array($item) ? trim((string) ($item['ref'] ?? '')) : '';

            if ('' === $ref || !\in_array($ref, $refs, true) || isset($out[$ref])) {
                continue;
            }

            $out[$ref] = ['ref' => $ref, 'caption' => mb_substr(trim((string) ($item['caption'] ?? '')), 0, 300)];
        }

        return \array_slice(array_values($out), 0, 3);
    }

    /**
     * Sections the model added for topics outside the seven areas.
     *
     * @return list<array{title: string, intro: string, points: list<array{title: string, text: string, kind: string}>}>
     */
    private function sections(mixed $list): array
    {
        $out = [];

        foreach ((array) $list as $section) {
            if (!\is_array($section) || '' === trim((string) ($section['title'] ?? ''))) {
                continue;
            }

            $points = [];

            foreach ((array) ($section['points'] ?? []) as $point) {
                if (!\is_array($point) || '' === trim((string) ($point['title'] ?? ''))) {
                    continue;
                }

                $points[] = [
                    'title' => mb_substr(trim((string) $point['title']), 0, 160),
                    'text' => mb_substr(trim((string) ($point['text'] ?? '')), 0, 600),
                    'kind' => 'good' === ($point['kind'] ?? '') ? 'good' : 'warn',
                ];
            }

            if ([] === $points) {
                continue;
            }

            $out[] = [
                'title' => mb_substr(trim((string) $section['title']), 0, 120),
                'intro' => mb_substr(trim((string) ($section['intro'] ?? '')), 0, 500),
                'points' => \array_slice($points, 0, 4),
            ];
        }

        return \array_slice($out, 0, 2);
    }

    private function parseJson(string $content): array|null
    {
        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $content) ?? $content;
        $json = json_decode($content, true);

        if (\is_array($json)) {
            return $json;
        }

        // Model wrapped the JSON in prose: take the outermost braces.
        $start = strpos($content, '{');
        $end = strrpos($content, '}');

        if (false !== $start && false !== $end && $end > $start) {
            $json = json_decode(substr($content, $start, $end - $start + 1), true);
        }

        return \is_array($json) ? $json : null;
    }

    /**
     * @param list<string> $keys
     *
     * @return list<array<string, string>>
     */
    private function items(mixed $list, array $keys): array
    {
        $out = [];

        foreach ((array) $list as $item) {
            if (!\is_array($item)) {
                continue;
            }

            $row = [];

            foreach ($keys as $key) {
                $row[$key] = mb_substr(trim((string) ($item[$key] ?? '')), 0, 600);
            }

            if ('' !== $row['title']) {
                if (isset($row['effort']) && !\in_array($row['effort'], ['small', 'medium', 'large'], true)) {
                    $row['effort'] = 'small';
                }

                $out[] = $row;
            }
        }

        return \array_slice($out, 0, 5);
    }
}
