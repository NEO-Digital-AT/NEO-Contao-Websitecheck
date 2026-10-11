<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Backend;

use Contao\CoreBundle\Exception\ResponseException;
use Doctrine\DBAL\Connection;
use Neo\WebsitecheckBundle\Settings;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * "Test connections" in the settings: calls every external service the check
 * needs, from this server, and shows the exact answer or error — plus the
 * errors recorded by the latest checks. Keys are never shown (they are masked
 * in every message).
 */
final class ApiTestOperation
{
    public function __construct(
        private readonly Settings $settings,
        private readonly Connection $db,
        private readonly HttpClientInterface $apiClient,
    ) {
    }

    public function __invoke(): string
    {
        $ai = $this->settings->ai();
        $psiKey = $this->settings->pageSpeedKey();
        $brevoKey = $this->settings->brevoApiKey();
        $secrets = array_filter([$psiKey, (string) $ai['api_key'], $brevoKey]);
        $plain = HttpClient::create(['timeout' => 20, 'max_duration' => 25]);
        $rows = [];

        $rows[] = $this->probe('Internet (google.com)', static fn () => $plain->request('GET', 'https://www.google.com/generate_204'), $secrets);

        $rows[] = '' === $psiKey
            ? ['PageSpeed', 'missing', 'No PageSpeed key stored.']
            // An invalid URL answers within a second: 400 "invalid value" = key accepted, "API key not valid" = key wrong.
            : $this->probe('PageSpeed (Google API) — key '.$this->fingerprint($psiKey), fn () => $this->apiClient->request('GET', 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed', ['query' => ['url' => 'not-a-url', 'key' => $psiKey], 'timeout' => 20, 'max_duration' => 25]), $secrets, [400]);

        $rows[] = '' === trim((string) $ai['api_key'])
            ? ['AI', 'missing', 'No AI key stored.']
            : $this->probe('AI: '.rtrim((string) $ai['base_url'], '/').'/models — key '.$this->fingerprint((string) $ai['api_key']), fn () => $this->apiClient->request('GET', rtrim((string) $ai['base_url'], '/').'/models', ['headers' => ['Authorization' => 'Bearer '.$ai['api_key']], 'timeout' => 20, 'max_duration' => 25]), $secrets);

        $rows[] = '' === $brevoKey
            ? ['Brevo', 'missing', 'No Brevo key (NEO Brevo module).']
            : $this->probe('Brevo (api.brevo.com/v3/account)', static fn () => $plain->request('GET', 'https://api.brevo.com/v3/account', ['headers' => ['api-key' => $brevoKey, 'accept' => 'application/json']]), $secrets);

        $html = '<!doctype html><meta charset="utf-8"><title>Website check: connections</title>'
            .'<style>body{font:14px/1.5 system-ui,sans-serif;margin:24px;max-width:1100px}table{border-collapse:collapse;width:100%}td,th{border:1px solid #ccc;padding:6px 8px;text-align:left;vertical-align:top}.ok{color:#0a6b2d;font-weight:600}.fail,.missing{color:#a40e26;font-weight:600}code{white-space:pre-wrap;word-break:break-all}</style>'
            .'<h1>Website check: connections from this server</h1><table><tr><th>Service</th><th>Result</th><th>Details</th></tr>';

        foreach ($rows as [$name, $state, $detail]) {
            $html .= sprintf('<tr><td>%s</td><td class="%s">%s</td><td><code>%s</code></td></tr>', $this->e($name), $state, $this->e($state), $this->e($detail));
        }

        $html .= '</table><h2>Latest checks</h2><table><tr><th>#</th><th>Started</th><th>Domain</th><th>Status</th><th>Recorded errors</th></tr>';

        try {
            $checks = $this->db->fetchAllAssociative('SELECT id, created, domain, status, data FROM tl_neo_websitecheck ORDER BY id DESC LIMIT 8');
        } catch (\Throwable) {
            $checks = [];
        }

        foreach ($checks as $c) {
            $data = json_decode((string) $c['data'], true) ?: [];
            $errors = (array) ($data['errors'] ?? []);

            if (empty($data['psi'])) {
                $errors['psi'] = 'no PageSpeed measurement in this check';
            }

            if (empty($data['ai'])) {
                $errors['ai'] = 'no AI reading in this check';
            }

            foreach ((array) ($data['psi'] ?? []) as $key => $psi) {
                if (!empty($psi['error'])) {
                    $errors['psi.'.$key] = $psi['error'];
                }
            }

            $html .= sprintf(
                '<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td><td><code>%s</code></td></tr>',
                (int) $c['id'],
                date('d.m.Y H:i:s', (int) $c['created']),
                $this->e((string) $c['domain']),
                $this->e((string) $c['status']),
                $this->e($this->mask([] === $errors ? '–' : json_encode($errors, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), $secrets)),
            );
        }

        throw new ResponseException(new Response($html.'</table>', 200, ['Content-Type' => 'text/html; charset=utf-8', 'Cache-Control' => 'no-store']));
    }

    /**
     * @param list<string> $secrets
     * @param list<int>    $alsoOk  status codes that still prove the key and connection work
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private function probe(string $name, callable $call, array $secrets, array $alsoOk = []): array
    {
        $t = microtime(true);

        try {
            $response = $call();
            $status = $response->getStatusCode();
            $body = mb_substr($response->getContent(false), 0, 400);
            $ms = (int) ((microtime(true) - $t) * 1000);
            $ok = ($status >= 200 && $status < 300) || (\in_array($status, $alsoOk, true) && !str_contains($body, 'API key not valid') && !str_contains($body, 'PERMISSION_DENIED'));

            return [$name, $ok ? 'ok' : 'fail', $this->mask(sprintf('HTTP %d in %d ms: %s', $status, $ms, $body), $secrets)];
        } catch (\Throwable $e) {
            return [$name, 'fail', $this->mask(sprintf('%s: %s', $e::class, $e->getMessage()), $secrets)];
        }
    }

    /** @param list<string> $secrets */
    private function mask(string $text, array $secrets): string
    {
        foreach ($secrets as $secret) {
            if (\strlen($secret) >= 6) {
                $text = str_replace([$secret, rawurlencode($secret)], '***', $text);
            }
        }

        return $text;
    }

    /** Enough to compare with the original (length, start, end), never the key itself. */
    private function fingerprint(string $key): string
    {
        return sprintf('%d chars, %s…%s', mb_strlen($key), mb_substr($key, 0, 4), mb_substr($key, -2));
    }

    private function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
