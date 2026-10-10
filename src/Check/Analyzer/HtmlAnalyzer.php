<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Check\Analyzer;

/**
 * Server-side HTML analysis of one page: title/description, headings, images
 * without alt text, language, viewport, canonical, structured data, links,
 * contact signals, visible text (for the AI review) and the raw material the
 * legal and AI-visibility analyzers build on.
 */
final class HtmlAnalyzer
{
    private const CMP_SIGNATURES = ['cookiebot', 'usercentrics', 'borlabs', 'complianz', 'onetrust', 'cookieconsent', 'klaro', 'consentmanager', 'iubenda', 'cookiefirst', 'cookieyes', 'termly', 'quantcast', 'didomi', 'cmp', 'cookie-banner', 'cookiebanner', 'cookie-consent', 'cookie_consent', 'ccm19', 'real-cookie-banner', 'cookie-notice', 'gdpr'];

    /**
     * @return array<string, mixed>
     */
    public function analyze(string $url, string $html): array
    {
        $html = $this->sanitize($html);
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET);
        libxml_clear_errors();
        $xp = new \DOMXPath($doc);

        $text = static fn (\DOMNode|null $n): string => null === $n ? '' : trim(preg_replace('/\s+/u', ' ', $n->textContent) ?? '');
        $attr = static fn (string $query) => ($n = $xp->query($query)->item(0)) instanceof \DOMAttr ? trim($n->value) : '';

        $title = $text($xp->query('//head/title')->item(0) ?? $xp->query('//title')->item(0));
        $description = $attr('//meta[translate(@name,"DESCRIPTION","description")="description"]/@content');
        $lang = $attr('/html/@lang');
        $viewport = $attr('//meta[@name="viewport"]/@content');
        $canonical = $attr('//link[@rel="canonical"]/@href');
        $robotsMeta = strtolower($attr('//meta[@name="robots"]/@content'));

        $headings = [];

        foreach ($xp->query('//h1|//h2|//h3|//h4|//h5|//h6') ?: [] as $h) {
            $headings[] = ['level' => (int) substr($h->nodeName, 1), 'text' => mb_substr($text($h), 0, 120)];
        }

        $h1 = array_values(array_filter($headings, static fn (array $h): bool => 1 === $h['level']));
        $skips = 0;
        $prev = 0;

        foreach ($headings as $h) {
            if ($prev > 0 && $h['level'] > $prev + 1) {
                ++$skips;
            }

            $prev = $h['level'];
        }

        $images = $xp->query('//img') ?: [];
        $imgTotal = 0;
        $imgNoAlt = 0;

        foreach ($images as $img) {
            if (!$img instanceof \DOMElement) {
                continue;
            }

            ++$imgTotal;

            if (!$img->hasAttribute('alt')) {
                ++$imgNoAlt;
            }
        }

        // Links: internal/external, legal pages, contact signals.
        $base = $this->baseUrl($url);
        $host = parse_url($base, PHP_URL_HOST);
        $links = ['internal' => [], 'external' => 0, 'nav' => [], 'legal' => [], 'tel' => [], 'mailto' => 0, 'contact' => [], 'social' => [], 'maps' => false];
        $legalWords = ['impressum' => 'imprint', 'imprint' => 'imprint', 'legal notice' => 'imprint', 'legal-notice' => 'imprint', 'mentions légales' => 'imprint', 'datenschutz' => 'privacy', 'privacy' => 'privacy', 'dsgvo' => 'privacy', 'gdpr' => 'privacy', 'agb' => 'terms', 'terms' => 'terms'];

        foreach ($xp->query('//a[@href]') ?: [] as $a) {
            if (!$a instanceof \DOMElement) {
                continue;
            }

            $href = trim($a->getAttribute('href'));
            $label = mb_strtolower($text($a));

            if (str_starts_with($href, 'tel:')) {
                $links['tel'][] = $href;
                continue;
            }

            if (str_starts_with($href, 'mailto:')) {
                ++$links['mailto'];
                continue;
            }

            if ('' === $href || str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) {
                continue;
            }

            $abs = $this->absolute($href, $base);

            if ('' === $abs) {
                continue;
            }

            $linkHost = parse_url($abs, PHP_URL_HOST);

            if (preg_match('~(google\.[a-z.]+/maps|maps\.app\.goo\.gl|goo\.gl/maps|g\.page)~i', $abs)) {
                $links['maps'] = true;
            }

            if (preg_match('~(facebook|instagram|linkedin|youtube|tiktok|xing|pinterest|x\.com|twitter)~i', (string) $linkHost)) {
                $links['social'][] = preg_replace('/^www\./', '', (string) $linkHost);
            }

            if ($linkHost !== $host) {
                ++$links['external'];
                continue;
            }

            $links['internal'][$abs] = true;
            $path = mb_strtolower((string) parse_url($abs, PHP_URL_PATH));

            foreach ($legalWords as $word => $kind) {
                if (str_contains($label, $word) || str_contains($path, str_replace(' ', '-', $word))) {
                    $links['legal'][$kind] = $abs;
                }
            }

            if (preg_match('/kontakt|contact|anfrage|termin|booking|buchen|reserv/u', $label.' '.$path)) {
                $links['contact'][$abs] = true;
            }

            $inNav = null !== $xp->query('ancestor::nav|ancestor::header|ancestor::footer|ancestor::*[contains(concat(" ",normalize-space(@class)," ")," nav ") or contains(@class,"menu") or contains(@id,"nav")]', $a)->item(0);

            if ($inNav) {
                $links['nav'][$abs] = true;
            }
        }

        $links['internal'] = array_keys($links['internal']);
        $links['nav'] = array_keys($links['nav']);
        $links['contact'] = array_keys($links['contact']);
        $links['tel'] = array_values(array_unique($links['tel']));
        $links['social'] = array_values(array_unique($links['social']));

        // Structured data types (JSON-LD).
        $schema = [];

        foreach ($xp->query('//script[@type="application/ld+json"]') ?: [] as $script) {
            $json = json_decode($script->textContent, true);

            foreach ($this->schemaTypes(\is_array($json) ? $json : []) as $type) {
                $schema[$type] = true;
            }
        }

        $scripts = [];

        foreach ($xp->query('//script[@src]') ?: [] as $script) {
            if ($script instanceof \DOMElement) {
                $scripts[] = $script->getAttribute('src');
            }
        }

        $lowerHtml = mb_strtolower($html);
        $cmp = [];

        foreach (self::CMP_SIGNATURES as $sig) {
            if (str_contains($lowerHtml, $sig)) {
                $cmp[] = $sig;
            }
        }

        // Visible text in reading order (without scripts/styles/nav noise).
        foreach ($xp->query('//script|//style|//noscript|//template|//svg') ?: [] as $n) {
            $n->parentNode?->removeChild($n);
        }

        $body = $xp->query('//body')->item(0);
        // Visible text only: no scripts/styles, no hidden decoration such as icon-font ligatures
        // ("location_on", "edit_document"), which would otherwise end up in the AI's reading.
        foreach (iterator_to_array($xp->query('//body//script | //body//style | //body//noscript | //body//template | //body//svg | //body//*[@aria-hidden="true"] | //body//*[contains(concat(" ", normalize-space(@class), " "), " material-icons ") or contains(@class, "material-symbols")]') ?: []) as $node) {
            $node->parentNode?->removeChild($node);
        }

        $visible = $text($body);
        $words = str_word_count(strip_tags($visible)) ?: \count(preg_split('/\s+/u', $visible, -1, PREG_SPLIT_NO_EMPTY) ?: []);

        $hasOpeningHours = (bool) preg_match('/öffnungszeiten|opening hours|geöffnet|open(ed)? (from|mon|daily)|mo[\.\-–]|mon[\.\-–]|montag|monday/iu', $visible);
        $hasAddress = (bool) preg_match('/\b\d{4,5}\s+[A-ZÄÖÜ][a-zäöüß]+/u', $visible) || isset($schema['PostalAddress']) || isset($schema['LocalBusiness']);

        return [
            'url' => $url,
            'title' => $title,
            'titleLength' => mb_strlen($title),
            'description' => $description,
            'descriptionLength' => mb_strlen($description),
            'lang' => $lang,
            'viewport' => $viewport,
            'canonical' => $canonical,
            'noindex' => str_contains($robotsMeta, 'noindex'),
            'h1' => array_map(static fn (array $h): string => $h['text'], $h1),
            'h1Count' => \count($h1),
            'headingSkips' => $skips,
            'headings' => \array_slice($headings, 0, 40),
            'imgTotal' => $imgTotal,
            'imgNoAlt' => $imgNoAlt,
            'links' => $links,
            'schema' => array_keys($schema),
            'scripts' => \array_slice($scripts, 0, 60),
            'cmp' => $cmp,
            'words' => $words,
            'text' => mb_substr($visible, 0, 9000),
            'openingHours' => $hasOpeningHours,
            'address' => $hasAddress,
            'favicon' => '' !== $attr('//link[contains(@rel,"icon")]/@href'),
        ];
    }

    /** Collect @type values recursively (also @graph). */
    private function schemaTypes(array $json): array
    {
        $types = [];

        if (isset($json['@type'])) {
            foreach ((array) $json['@type'] as $t) {
                if (\is_string($t)) {
                    $types[] = $t;
                }
            }
        }

        foreach ($json as $value) {
            if (\is_array($value)) {
                $types = array_merge($types, $this->schemaTypes($value));
            }
        }

        return $types;
    }

    private function sanitize(string $html): string
    {
        if (!mb_check_encoding($html, 'UTF-8')) {
            $html = mb_convert_encoding($html, 'UTF-8', 'ISO-8859-1');
        }

        return $html;
    }

    private function baseUrl(string $url): string
    {
        $p = parse_url($url);

        return ($p['scheme'] ?? 'https').'://'.($p['host'] ?? '').(isset($p['path']) ? preg_replace('~/[^/]*$~', '/', $p['path']) : '/');
    }

    private function absolute(string $href, string $base): string
    {
        if (preg_match('~^https?://~i', $href)) {
            return $href;
        }

        if (str_starts_with($href, '//')) {
            return (parse_url($base, PHP_URL_SCHEME) ?: 'https').':'.$href;
        }

        if (preg_match('~^[a-z][a-z0-9+.-]*:~i', $href)) {
            return '';
        }

        $p = parse_url($base);
        $root = ($p['scheme'] ?? 'https').'://'.($p['host'] ?? '');

        if (str_starts_with($href, '/')) {
            return $root.$href;
        }

        if (str_starts_with($href, '?')) {
            return $root.($p['path'] ?? '/').$href;
        }

        return $root.rtrim($p['path'] ?? '/', '/').'/'.$href;
    }
}
