<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Newsletter;

use Contao\DataContainer;
use Neo\WebsitecheckBundle\Settings;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Back-end choices for the newsletter fields of a configuration (one per
 * language): the Brevo lists and templates, read live from the account with the
 * NEO Brevo module's key and cached for an hour — or the Contao newsletter
 * channels when that module is not installed. Without an API key, or when Brevo does not answer, the
 * fields stay plain number fields (see onload()), so nobody is locked out. The
 * stored value is always kept in the list, otherwise it would look empty and
 * get lost on save.
 */
final class BrevoOptions
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Settings $settings,
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    /**
     * onload_callback: the newsletter fields follow the system in use — Brevo
     * lists and templates (number fields when Brevo cannot be asked), Contao
     * newsletter channels (no template: Contao sends its own e-mail), or no
     * newsletter fields at all.
     */
    public function onload(DataContainer|null $dc = null): void
    {
        $table = 'tl_neo_websitecheck_config';
        $provider = $this->settings->newsletterProvider();

        if ('brevo' !== $provider) {
            foreach ((array) ($GLOBALS['TL_DCA'][$table]['palettes'] ?? []) as $name => $palette) {
                if (!\is_string($palette)) {
                    continue;
                }

                $GLOBALS['TL_DCA'][$table]['palettes'][$name] = 'contao' === $provider
                    ? str_replace(',nl_template_id', '', $palette)
                    : (string) preg_replace('/\{newsletter_legend[^}]*\}[^;]*;?/', '', $palette);
            }

            if ('contao' === $provider) {
                $GLOBALS['TL_DCA'][$table]['fields']['nl_list_id']['label'] = &$GLOBALS['TL_LANG'][$table]['nl_list_id_contao'];
                $GLOBALS['TL_DCA'][$table]['fields']['nl_list_id']['options_callback'] = [self::class, 'channels'];
                $GLOBALS['TL_DCA'][$table]['fields']['nl_redirect_page']['label'] = &$GLOBALS['TL_LANG'][$table]['nl_redirect_page_contao'];
            }

            return;
        }

        foreach (['nl_list_id' => 'lists', 'nl_template_id' => 'templates'] as $field => $kind) {
            if ([] === $this->fetch($kind)) {
                $GLOBALS['TL_DCA']['tl_neo_websitecheck_config']['fields'][$field]['inputType'] = 'text';
                $GLOBALS['TL_DCA']['tl_neo_websitecheck_config']['fields'][$field]['eval']['rgxp'] = 'natural';
                unset($GLOBALS['TL_DCA']['tl_neo_websitecheck_config']['fields'][$field]['options_callback']);
            }
        }
    }

    /** @return array<int, string> */
    public function lists(DataContainer|null $dc = null): array
    {
        return $this->withStored($this->fetch('lists'), $dc);
    }

    /**
     * Contao newsletter channels (when the Contao newsletter module is used).
     *
     * @return array<int, string>
     */
    public function channels(DataContainer|null $dc = null): array
    {
        $options = [];

        try {
            foreach (\Contao\Database::getInstance()->execute('SELECT id, title FROM tl_newsletter_channel ORDER BY title')->fetchAllAssoc() as $row) {
                $options[(int) $row['id']] = $row['title'].' (#'.$row['id'].')';
            }
        } catch (\Throwable) {
        }

        return $this->withStored($options, $dc);
    }

    /** @return array<int, string> */
    public function templates(DataContainer|null $dc = null): array
    {
        return $this->withStored($this->fetch('templates'), $dc);
    }

    /**
     * @param array<int, string> $options
     *
     * @return array<int, string>
     */
    private function withStored(array $options, DataContainer|null $dc): array
    {
        $stored = (int) ($dc?->value ?? 0);

        if ($stored > 0 && !isset($options[$stored])) {
            $options[$stored] = '#'.$stored;
        }

        return $options;
    }

    /** @return array<int, string> id => "Name (#id)" */
    private function fetch(string $kind): array
    {
        // The key of the NEO Brevo module (back end) — never from a configuration file.
        $key = $this->settings->brevoApiKey();

        if ('' === $key) {
            return [];
        }

        $item = $this->cache->getItem('neo_websitecheck.brevo_'.$kind.'.'.md5($key));

        if ($item->isHit()) {
            return (array) $item->get();
        }

        $url = 'lists' === $kind
            ? 'https://api.brevo.com/v3/contacts/lists?limit=50&offset=0'
            : 'https://api.brevo.com/v3/smtp/templates?templateStatus=true&limit=100&offset=0';

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['api-key' => $key, 'Accept' => 'application/json'],
                'timeout' => 8,
            ]);
            $data = 200 === $response->getStatusCode() ? $response->toArray(false) : [];
        } catch (\Throwable) {
            $data = [];
        }

        $options = [];

        foreach ((array) ($data[$kind] ?? []) as $entry) {
            $id = (int) ($entry['id'] ?? 0);

            if ($id > 0) {
                $options[$id] = trim((string) ($entry['name'] ?? '')).' (#'.$id.')';
            }
        }

        // Cache only real answers; an outage should not hide the select for an hour.
        if ([] !== $options) {
            $item->set($options)->expiresAfter(3600);
            $this->cache->save($item);
        }

        return $options;
    }
}
