<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\I18n;

use Neo\WebsitecheckBundle\Lang;
use Neo\WebsitecheckBundle\Settings;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Visitor-facing texts for languages the bundle ships no translation file for.
 *
 * No language is special: the bundle ships translation files for eleven
 * languages (de, en, fr, nl, it, pl, cs, hu, sl, hr, es), and for
 * every other language the configured AI translates the visitor-facing part of
 * the catalogue once (findings, measures, form, report and e-mail wording —
 * not the back-end designer labels). The result is stored in
 * <storage_dir>/i18n/neo_websitecheck.<lang>.json and used by TranslatorDecorator
 * wherever that language has no own catalogue entry. A translation file in the
 * project (translations/neo_websitecheck.<lang>.yaml) always wins; the console
 * command neo:websitecheck:translate --export writes one to correct by hand.
 *
 * Generation runs in parallel chunks (a few seconds), is locked against
 * concurrent runs and needs the AI settings; without them the installation's
 * default language is used as before.
 */
final class AiCatalogue
{
    public const DOMAIN = 'neo_websitecheck';

    /** Key used to tell whether a language has its own catalogue. */
    private const PROBE = 'fe.submit';

    private const CHUNK = 60;

    /** @var array<string, array<string, string>> */
    private array $loaded = [];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Settings $settings,
        private readonly LoggerInterface|null $logger = null,
    ) {
    }

    /** @return array<string, string> stored AI translations of a language */
    public function messages(string $locale): array
    {
        $lang = Lang::primary($locale);

        if ('' === $lang) {
            return [];
        }

        if (!isset($this->loaded[$lang])) {
            $file = $this->file($lang);
            $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
            $this->loaded[$lang] = \is_array($data) ? array_map('strval', $data) : [];
        }

        return $this->loaded[$lang];
    }

    /** True when the language has neither a translation file nor a stored AI catalogue. */
    public function isMissing(TranslatorBagInterface $bag, string $locale): bool
    {
        $lang = Lang::primary($locale);

        if ('' === $lang || $this->hasOwnCatalogue($bag, $lang)) {
            return false;
        }

        return [] === $this->messages($lang);
    }

    public function hasOwnCatalogue(TranslatorBagInterface $bag, string $lang): bool
    {
        try {
            return $bag->getCatalogue($lang)->defines(self::PROBE, self::DOMAIN);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Translates the visitor-facing catalogue into the language (only keys not
     * yet stored unless $force). Returns the number of new texts.
     */
    public function generate(TranslatorBagInterface $bag, string $locale, bool $force = false): int
    {
        $lang = Lang::primary($locale);

        if ('' === $lang || !$this->settings->aiEnabled() || $this->hasOwnCatalogue($bag, $lang)) {
            return 0;
        }

        $dir = \dirname($this->file($lang));

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return 0;
        }

        $lock = fopen($dir.'/.lock-'.$lang, 'c');

        if (false === $lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            return 0; // another request is already translating this language
        }

        try {
            $source = $this->sourceMessages($bag);
            $done = $force ? [] : $this->messages($lang);
            $todo = array_diff_key($source, $done);

            if ([] === $todo) {
                return 0;
            }

            $translated = $this->translateChunks($todo, Lang::englishName($lang));
            $merged = array_merge($done, $translated);
            ksort($merged);

            file_put_contents($this->file($lang), json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->loaded[$lang] = $merged;

            return \count($translated);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function file(string $lang): string
    {
        return $this->settings->storageDir().'/i18n/'.self::DOMAIN.'.'.$lang.'.json';
    }

    /** @return array<string, string> English source texts a visitor can see (no back-end labels) */
    private function sourceMessages(TranslatorBagInterface $bag): array
    {
        $all = $bag->getCatalogue('en')->all(self::DOMAIN);

        return array_filter(
            array_map('strval', $all),
            static fn (string $text, string $key): bool => !str_starts_with($key, 'designer.') && '' !== trim($text),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * @param array<string, string> $todo
     *
     * @return array<string, string>
     */
    private function translateChunks(array $todo, string $language): array
    {
        $ai = $this->settings->ai();
        $system = <<<PROMPT
            You translate the texts of a website-check tool (findings, measures, form labels, report and e-mail wording) from English into {$language}.
            Rules:
            - Return one JSON object with exactly the same keys and the translated texts as values.
            - Keep placeholders such as %count%, %domain% or {{ name }} and all HTML tags exactly as they are.
            - Natural, plain wording for business owners without IT knowledge; formal address where the language has one.
            - Keep product and technical names (Google, Lighthouse, PageSpeed Insights, llms.txt, HTTPS, WCAG) as they are.
            - Typographic quotation marks of {$language} for format.quote_open / format.quote_close.
            PROMPT;

        $responses = [];

        foreach (array_chunk($todo, self::CHUNK, true) as $i => $chunk) {
            $responses[$i] = [$chunk, $this->httpClient->request('POST', rtrim((string) $ai['base_url'], '/').'/chat/completions', [
                'headers' => ['Authorization' => 'Bearer '.$ai['api_key'], 'Content-Type' => 'application/json'],
                'json' => [
                    'model' => $ai['model'],
                    'temperature' => 0.2,
                    'max_tokens' => 6000,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => json_encode($chunk, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                    ],
                ],
                'timeout' => max(30, (int) $ai['timeout']),
                'max_duration' => max(60, (int) $ai['timeout'] + 30),
            ])];
        }

        $out = [];

        // The requests above run in parallel; reading them in order just collects the answers.
        foreach ($responses as [$chunk, $response]) {
            try {
                $json = $response->toArray(false);
                $content = (string) ($json['choices'][0]['message']['content'] ?? '');
                $content = trim((string) preg_replace('/^```(?:json)?|```$/m', '', $content));
                $data = json_decode($content, true);
            } catch (\Throwable $e) {
                $this->logger?->warning('Website check: translating texts failed: '.$e->getMessage());

                continue;
            }

            if (!\is_array($data)) {
                continue;
            }

            foreach ($chunk as $key => $text) {
                $value = $data[$key] ?? null;

                // Only accept a translation that kept every placeholder.
                if (\is_string($value) && '' !== trim($value) && self::placeholders($value) === self::placeholders($text)) {
                    $out[$key] = $value;
                }
            }
        }

        return $out;
    }

    /** @return list<string> */
    private static function placeholders(string $text): array
    {
        preg_match_all('/%[a-z_]+%|\{\{\s*[a-z_.]+\s*\}\}/i', $text, $m);
        $list = array_map(static fn (string $p): string => (string) preg_replace('/\s+/', '', $p), $m[0]);
        sort($list);

        return $list;
    }
}
