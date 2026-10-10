<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Check;

use Neo\WebsitecheckBundle\Settings;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Fetches pages of the checked website with a size cap and short timeouts.
 * Several URLs are fetched concurrently (Symfony HttpClient streams).
 */
final class SiteFetcher
{
    private const MAX_BYTES = 1_500_000;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Settings $settings,
    ) {
    }

    /**
     * @param list<string> $urls
     *
     * @return array<string, array{status: int, url: string, body: string, headers: array<string, list<string>>, redirects: list<string>, time: float, error: string}>
     */
    public function fetchAll(array $urls): array
    {
        $responses = [];
        $started = microtime(true);

        foreach (array_values(array_unique($urls)) as $url) {
            try {
                $responses[$url] = $this->httpClient->request('GET', $url, [
                    'headers' => [
                        'User-Agent' => $this->settings->userAgent(),
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,text/plain;q=0.8,*/*;q=0.5',
                        'Accept-Language' => 'de,en;q=0.8',
                    ],
                    'on_progress' => static function (int $dlNow) use ($url): void {
                        if ($dlNow > self::MAX_BYTES) {
                            throw new \RuntimeException('Response too large: '.$url);
                        }
                    },
                ]);
            } catch (\Throwable $e) {
                $responses[$url] = $e;
            }
        }

        $out = [];

        foreach ($responses as $url => $response) {
            $out[$url] = $this->collect($url, $response, $started);
        }

        return $out;
    }

    /**
     * @return array{status: int, url: string, body: string, headers: array<string, list<string>>, redirects: list<string>, time: float, error: string}
     */
    public function fetch(string $url): array
    {
        return $this->fetchAll([$url])[$url];
    }

    private function collect(string $url, ResponseInterface|\Throwable $response, float $started): array
    {
        $result = ['status' => 0, 'url' => $url, 'body' => '', 'headers' => [], 'redirects' => [], 'time' => 0.0, 'error' => ''];

        if ($response instanceof \Throwable) {
            $result['error'] = $response->getMessage();

            return $result;
        }

        try {
            $result['status'] = $response->getStatusCode();
            $result['headers'] = $response->getHeaders(false);
            $info = $response->getInfo();
            $result['url'] = (string) ($info['url'] ?? $url);
            $result['redirects'] = array_values(array_filter(array_map(
                static fn (string $line): string => preg_match('~^location:\s*(\S+)~i', $line, $m) ? $m[1] : '',
                (array) ($info['response_headers'] ?? []),
            )));
            $result['time'] = round((float) ($info['total_time'] ?? microtime(true) - $started), 3);
            $result['body'] = $response->getContent(false);
        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }
}
