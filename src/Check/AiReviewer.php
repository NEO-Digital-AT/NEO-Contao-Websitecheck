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
     *
     * @return array<string, mixed>|null null when disabled or the answer was unusable
     */
    public function review(string $domain, array $pages, string|null $screenshotPath, string $locale): array|null
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
             "summary": string (2-3 sentences for the cover page, honest, no sales pressure)}
            PROMPT;

        $userContent = [['type' => 'text', 'text' => 'Website: '.$domain.$corpus]];

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
            'model' => (string) ($json['model'] ?? $ai['model']),
            'usage' => $json['usage'] ?? null,
        ];
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
