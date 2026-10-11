<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle;

/**
 * Typed read access to the processed "neo_websitecheck" configuration.
 */
final class Settings
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly array $config,
        private readonly string $projectDir,
        private readonly Check\SettingsRepository|null $db = null,
    ) {
    }

    /** Back-end settings repository (null in unit tests). */
    public function db(): Check\SettingsRepository|null
    {
        return $this->db;
    }

    public function projectDir(): string
    {
        return $this->projectDir;
    }

    public function storageDir(): string
    {
        return rtrim((string) $this->config['storage_dir'], '/');
    }

    public function userAgent(): string
    {
        return (string) $this->config['user_agent'];
    }

    public function pageSpeedKey(): string
    {
        return trim((string) $this->config['pagespeed']['api_key']);
    }

    public function pageSpeedTimeout(): int
    {
        return (int) $this->config['pagespeed']['timeout'];
    }

    /** @return array{base_url: string, api_key: string, model: string, max_tokens: int, timeout: int, send_screenshot: bool} */
    public function ai(): array
    {
        return $this->config['ai'];
    }

    public function aiEnabled(): bool
    {
        return '' !== trim((string) $this->config['ai']['api_key']) && '' !== trim((string) $this->config['ai']['model']);
    }

    /**
     * Sender settings: back end first, config.yaml as fallback.
     *
     * @return array{from_email: string, from_name: string, reply_to: string, notify_email: string}
     */
    public function mail(): array
    {
        $mail = $this->config['mail'];

        foreach (['from_email', 'from_name', 'reply_to', 'notify_email'] as $key) {
            $value = $this->db?->string($key) ?? '';

            if ('' !== $value) {
                $mail[$key] = $value;
            }
        }

        return $mail;
    }

    /**
     * Brand/design settings: back end first (logo and portrait as project-relative
     * paths, colours with "#"), config.yaml as fallback. Adds contact_page /
     * privacy_page ids for the controllers to turn into URLs.
     *
     * @return array<string, string|int>
     */
    public function brand(): array
    {
        $brand = $this->config['brand'];
        $brand['logo_dark'] = (string) ($brand['logo_dark'] ?? '');
        $brand['contact_page'] = 0;
        $brand['privacy_page'] = 0;

        if (null === $this->db) {
            return $brand;
        }

        foreach (['brand_name' => 'name', 'owner_name' => 'owner_name', 'phone' => 'phone', 'website_url' => 'website_url'] as $column => $key) {
            $value = $this->db->string($column);

            if ('' !== $value) {
                $brand[$key] = $value;
            }
        }

        foreach (['logo' => 'logo', 'logo_dark' => 'logo_dark', 'portrait' => 'portrait'] as $column => $key) {
            $path = $this->db->filePath($column);

            if ('' !== $path) {
                $brand[$key] = $path;
            }
        }

        foreach (['color_primary', 'color_accent'] as $key) {
            $color = $this->db->color($key);

            if ('' !== $color) {
                $brand[$key] = $color;
            }
        }

        $brand['contact_page'] = $this->db->int('contact_page');
        $brand['privacy_page'] = $this->db->int('privacy_page');

        return $brand;
    }

    public function checksPerIpPerHour(): int
    {
        return $this->db?->int('checks_per_ip_hour') ?: (int) $this->config['limits']['checks_per_ip_per_hour'];
    }

    public function checksPerIpPerDay(): int
    {
        return $this->db?->int('checks_per_ip') ?: (int) $this->config['limits']['checks_per_ip_per_day'];
    }

    public function cacheHours(): int
    {
        // 0 in the back end means "from config"; the YAML may legitimately be 0.
        return $this->db?->int('cache_hours') ?: (int) $this->config['limits']['cache_hours'];
    }

    public function maxPages(): int
    {
        return min(8, $this->db?->int('max_pages') ?: (int) $this->config['limits']['max_pages']);
    }

    public function retentionDays(): int
    {
        return $this->db?->int('retention_days') ?: (int) $this->config['limits']['retention_days'];
    }

    public function pdfEngine(): string
    {
        return (string) $this->config['pdf']['engine'];
    }

    public function gotenbergUrl(): string
    {
        return rtrim(trim((string) $this->config['pdf']['gotenberg_url']), '/');
    }

    /** @return array<string, mixed> */
    public function newsletter(): array
    {
        return $this->config['newsletter'];
    }

    /**
     * Newsletter settings for one configuration (= one language on multilingual
     * sites): list, double-opt-in template and confirmation page of the
     * configuration, the config.yaml values where it leaves them empty.
     *
     * @return array<string, mixed> config.yaml keys plus brevo_redirect_page (page id or 0)
     */
    public function newsletterFor(int $configId = 0, string $locale = ''): array
    {
        $nl = $this->config['newsletter'] + ['brevo_redirect_page' => 0];

        if (null !== $this->db && ($configId > 0 || '' !== $locale)) {
            foreach (['nl_list_id' => 'brevo_list_id', 'nl_template_id' => 'brevo_template_id', 'nl_redirect_page' => 'brevo_redirect_page'] as $column => $key) {
                $value = (int) $this->db->configValue($configId, $locale, $column);

                if ($value > 0) {
                    $nl[$key] = $value;
                }
            }
        }

        return $nl;
    }

    /** True when a visitor of this configuration/language can really be signed up (provider, key, list, template, confirmation page). */
    public function newsletterEnabled(int $configId = 0, string $locale = ''): bool
    {
        $nl = $this->newsletterFor($configId, $locale);

        // The confirmation page is the customer's choice too: without it there is no
        // sign-up (Brevo needs it, and we never guess a page of someone else's site).
        return 'brevo' === $nl['provider'] && '' !== trim((string) $nl['brevo_api_key']) && (int) $nl['brevo_list_id'] > 0 && (int) $nl['brevo_template_id'] > 0
            && ((int) $nl['brevo_redirect_page'] > 0 || '' !== trim((string) $nl['brevo_redirect_url']));
    }

    public function dataLayer(): bool
    {
        return (bool) $this->config['tracking']['data_layer'];
    }
}
