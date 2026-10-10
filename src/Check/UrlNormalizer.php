<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Check;

/**
 * Turns visitor input ("Ihre-Firma.at", "https://www.example.com/shop") into a
 * checkable https URL and refuses anything that is not a public website
 * (localhost, private networks, IP literals, bare words).
 */
final class UrlNormalizer
{
    /**
     * @return array{domain: string, url: string}|null
     */
    public function normalize(string $input): array|null
    {
        $input = trim(mb_strtolower($input));
        $input = preg_replace('/\s+/', '', $input) ?? '';

        if ('' === $input || \strlen($input) > 253) {
            return null;
        }

        if (!preg_match('~^[a-z][a-z0-9+.-]*://~', $input)) {
            $input = 'https://'.$input;
        }

        $parts = parse_url($input);

        if (!\is_array($parts) || !isset($parts['host']) || isset($parts['user'], $parts['pass'])) {
            return null;
        }

        $host = $parts['host'];

        // IDN → ASCII so DNS and HTTP work with umlaut domains.
        if (preg_match('/[^\x20-\x7e]/', $host) && \function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

            if (\is_string($ascii) && '' !== $ascii) {
                $host = $ascii;
            }
        }

        if (!preg_match('/^([a-z0-9-]{1,63}\.)+[a-z]{2,63}$/', $host) || str_starts_with($host, '-')) {
            return null;
        }

        if (!$this->isPublicHost($host)) {
            return null;
        }

        $path = $parts['path'] ?? '/';
        $path = '' === $path ? '/' : $path;

        return [
            'domain' => preg_replace('/^www\./', '', $host) ?? $host,
            'url' => 'https://'.$host.$path,
        ];
    }

    /** Public DNS name that does not resolve to a private/loopback range (SSRF guard). */
    public function isPublicHost(string $host): bool
    {
        if (\in_array($host, ['localhost', 'localhost.localdomain'], true) || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return false;
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);

        if (!\is_array($records) || [] === $records) {
            return false;
        }

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;

            if (null !== $ip && false === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }
}
