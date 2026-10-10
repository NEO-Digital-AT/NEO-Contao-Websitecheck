<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Check;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;

/**
 * Reads the back-end settings (tl_neo_websitecheck_settings, one row) and the
 * per-language texts (tl_neo_websitecheck_text) through DBAL, cached for the
 * request. Missing tables (before the first contao:migrate) simply yield no
 * overrides, so the bundle keeps working from config.yaml alone.
 */
final class SettingsRepository
{
    /** @var array<string, mixed>|null|false */
    private array|null|false $row = false;

    /** @var array<string, array<string, mixed>|null> */
    private array $texts = [];

    public function __construct(private readonly Connection $db)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function row(): array|null
    {
        if (false === $this->row) {
            try {
                $row = $this->db->fetchAssociative('SELECT * FROM tl_neo_websitecheck_settings ORDER BY id ASC LIMIT 1');
                $this->row = false === $row ? null : $row;
            } catch (DbalException) {
                $this->row = null;
            }
        }

        return $this->row;
    }

    public function string(string $column): string
    {
        return trim((string) ($this->row()[$column] ?? ''));
    }

    public function int(string $column): int
    {
        return (int) ($this->row()[$column] ?? 0);
    }

    /** Hex colour with leading "#", or '' when unset. */
    public function color(string $column): string
    {
        $v = ltrim($this->string($column), '#');

        return preg_match('/^[0-9a-fA-F]{6}$/', $v) ? '#'.strtolower($v) : '';
    }

    /** Project-relative path of a file picked in the back end (UUID → tl_files.path), or ''. */
    public function filePath(string $column): string
    {
        $uuid = $this->row()[$column] ?? null;

        if (null === $uuid || '' === $uuid) {
            return '';
        }

        try {
            $path = $this->db->fetchOne('SELECT path FROM tl_files WHERE uuid = ?', [$uuid]);
        } catch (DbalException) {
            return '';
        }

        return \is_string($path) ? $path : '';
    }

    /** Custom text for a language and column, '' when not set. */
    public function text(string $language, string $column): string
    {
        if (!\array_key_exists($language, $this->texts)) {
            try {
                $row = $this->db->fetchAssociative('SELECT * FROM tl_neo_websitecheck_text WHERE language = ? ORDER BY id ASC LIMIT 1', [$language]);
                $this->texts[$language] = false === $row ? null : $row;
            } catch (DbalException) {
                $this->texts[$language] = null;
            }
        }

        return trim((string) ($this->texts[$language][$column] ?? ''));
    }

    public function reset(): void
    {
        $this->row = false;
        $this->texts = [];
    }
}
