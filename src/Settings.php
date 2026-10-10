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
    ) {
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

    /** @return array{from_email: string, from_name: string, reply_to: string, notify_email: string} */
    public function mail(): array
    {
        return $this->config['mail'];
    }

    /** @return array<string, string> */
    public function brand(): array
    {
        return $this->config['brand'];
    }

    public function checksPerIpPerDay(): int
    {
        return (int) $this->config['limits']['checks_per_ip_per_day'];
    }

    public function cacheHours(): int
    {
        return (int) $this->config['limits']['cache_hours'];
    }

    public function maxPages(): int
    {
        return (int) $this->config['limits']['max_pages'];
    }

    public function retentionDays(): int
    {
        return (int) $this->config['limits']['retention_days'];
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

    public function newsletterEnabled(): bool
    {
        return 'none' !== $this->config['newsletter']['provider'];
    }

    public function dataLayer(): bool
    {
        return (bool) $this->config['tracking']['data_layer'];
    }
}
