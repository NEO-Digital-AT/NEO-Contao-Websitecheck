<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Check;

/**
 * Picks the pages worth checking: start page plus the most important
 * sub-pages found in the navigation and the sitemap (offer, about, contact,
 * imprint/privacy are detected by keywords in several languages). Never more
 * than Settings::maxPages() — a site with 50 pages is represented by five.
 */
final class PageDiscovery
{
    private const KEYWORDS = [
        'offer' => ['leistung', 'angebot', 'service', 'produkt', 'preis', 'pricing', 'shop', 'zimmer', 'rooms', 'menu', 'speisekarte', 'portfolio', 'referenz', 'project', 'module', 'features', 'solutions', 'loesung', 'lösung'],
        'about' => ['ueber', 'über', 'about', 'team', 'wir', 'unternehmen', 'firma', 'company', 'philosophie', 'geschichte', 'story'],
        'contact' => ['kontakt', 'contact', 'anfrage', 'termin', 'booking', 'buchen', 'reserv', 'anfahrt', 'standort', 'location'],
        'legal' => ['impressum', 'imprint', 'legal', 'datenschutz', 'privacy', 'agb', 'terms'],
    ];

    /**
     * @param list<string> $navLinks     absolute URLs found in <nav>/<header>/<footer>
     * @param list<string> $sitemapUrls  absolute URLs from sitemap.xml
     *
     * @return list<array{url: string, role: string}>
     */
    public function select(string $homeUrl, array $navLinks, array $sitemapUrls, int $max): array
    {
        $home = $this->canonical($homeUrl);
        $host = parse_url($home, PHP_URL_HOST);
        $candidates = [];

        foreach (array_merge($navLinks, $sitemapUrls) as $url) {
            $url = $this->canonical($url);

            if ('' === $url || parse_url($url, PHP_URL_HOST) !== $host || $url === $home) {
                continue;
            }

            $path = strtolower((string) parse_url($url, PHP_URL_PATH));

            if (preg_match('~\.(pdf|jpe?g|png|gif|svg|webp|zip|docx?|xlsx?|mp4|xml)$~', $path)) {
                continue;
            }

            // Depth: shallow pages first, blog posts and deep listings last.
            $depth = substr_count(trim($path, '/'), '/');
            $candidates[$url] = ['url' => $url, 'role' => $this->role($path), 'depth' => $depth, 'nav' => \in_array($url, array_map($this->canonical(...), $navLinks), true)];
        }

        $picked = [['url' => $home, 'role' => 'home']];
        $usedRoles = ['home' => true];

        // One page per role in priority order, nav links preferred, then fill up with shallow nav pages.
        foreach (['offer', 'about', 'contact', 'legal'] as $role) {
            if (\count($picked) >= $max) {
                break;
            }

            $best = null;

            foreach ($candidates as $c) {
                if ($c['role'] !== $role) {
                    continue;
                }

                if (null === $best || ($c['nav'] && !$best['nav']) || ($c['nav'] === $best['nav'] && $c['depth'] < $best['depth'])) {
                    $best = $c;
                }
            }

            if (null !== $best) {
                $picked[] = ['url' => $best['url'], 'role' => $role];
                $usedRoles[$role] = true;
                unset($candidates[$best['url']]);
            }
        }

        uasort($candidates, static fn (array $a, array $b): int => [$b['nav'], $a['depth']] <=> [$a['nav'], $b['depth']]);

        foreach ($candidates as $c) {
            if (\count($picked) >= $max) {
                break;
            }

            if ('legal' === $c['role'] || $c['depth'] > 1) {
                continue;
            }

            $picked[] = ['url' => $c['url'], 'role' => 'other'];
        }

        return $picked;
    }

    private function role(string $path): string
    {
        foreach (self::KEYWORDS as $role => $words) {
            foreach ($words as $word) {
                if (str_contains($path, $word)) {
                    return $role;
                }
            }
        }

        return 'other';
    }

    private function canonical(string $url): string
    {
        $url = trim($url);
        $parts = parse_url($url);

        if (!\is_array($parts) || !isset($parts['host'])) {
            return '';
        }

        $path = $parts['path'] ?? '/';
        $path = preg_replace('~/+~', '/', $path) ?? '/';

        if ('' === $path) {
            $path = '/';
        }

        // Drop fragments and tracking queries; keep meaningful queries (rare on key pages).
        $query = isset($parts['query']) && !preg_match('/^(utm_|fbclid|gclid)/', $parts['query']) ? '?'.$parts['query'] : '';

        $path = rtrim($path, '/');

        if ('' === $path) {
            $path = '/';
        }

        return strtolower($parts['scheme'] ?? 'https').'://'.strtolower($parts['host']).$path.$query;
    }
}
