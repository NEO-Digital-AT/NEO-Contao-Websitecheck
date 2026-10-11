<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Check;

use Neo\WebsitecheckBundle\Settings;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The models the AI provider offers (OpenAI-compatible `GET {base}/models`), so
 * the back end shows a searchable selection instead of a field to type an
 * identifier into. Empty without a key or when the provider does not answer —
 * the field then stays a text field, so nobody locks themselves out. Cached for
 * an hour.
 */
final class ModelCatalog
{
    private const TTL = 3600;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheItemPoolInterface $cache,
        private readonly Settings $settings,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** @return array<string, string> model id => label */
    public function models(): array
    {
        $ai = $this->settings->ai();
        $key = trim((string) $ai['api_key']);
        $baseUrl = rtrim(trim((string) $ai['base_url']), '/');

        if ('' === $key || '' === $baseUrl) {
            return [];
        }

        $item = $this->cache->getItem('neo_websitecheck_models_'.hash('sha256', $baseUrl.'|'.$key));

        if ($item->isHit()) {
            return (array) $item->get();
        }

        try {
            $data = $this->httpClient->request('GET', $baseUrl.'/models', [
                'headers' => ['Authorization' => 'Bearer '.$key, 'Accept' => 'application/json'],
                'timeout' => 10,
                'max_duration' => 15,
            ])->toArray(false);
        } catch (\Throwable $e) {
            $this->logger->warning('NEO Website-Check: could not load the model list: '.$e->getMessage());

            return [];
        }

        $models = [];

        foreach ((array) ($data['data'] ?? []) as $entry) {
            $id = trim((string) ($entry['id'] ?? ''));

            if ('' !== $id) {
                $models[$id] = $id;
            }
        }

        if ([] === $models) {
            return [];
        }

        ksort($models);
        $item->set($models)->expiresAfter(self::TTL);
        $this->cache->save($item);

        return $models;
    }
}
