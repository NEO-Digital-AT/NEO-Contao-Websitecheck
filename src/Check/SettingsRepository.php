<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Check;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;

/**
 * Reads the back-end settings (tl_neo_websitecheck_settings, one row) and the
 * configurations per language (tl_neo_websitecheck_config) through DBAL, cached for the
 * request. Missing tables (before the first contao:migrate) simply yield no
 * overrides, so the bundle keeps working from config.yaml alone.
 */
final class SettingsRepository
{
    /** @var array<string, mixed>|null|false */
    private array|null|false $row = false;

    /** @var array<string, array<string, mixed>|null> */
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
    /**
     * The configuration record to use: by id when given and existing, else the
     * first one of the language, else the first one at all. Null without any.
     *
     * @return array<string, mixed>|null
     */
    public function config(int $id = 0, string $language = ''): array|null
    {
        $language = substr($language, 0, 2);
        $key = $id.'|'.$language;

        if (!\array_key_exists($key, $this->texts)) {
            $row = false;

            try {
                if ($id > 0) {
                    $row = $this->db->fetchAssociative('SELECT * FROM tl_neo_websitecheck_config WHERE id = ?', [$id]);
                }

                if (false === $row && '' !== $language) {
                    $row = $this->db->fetchAssociative('SELECT * FROM tl_neo_websitecheck_config WHERE language = ? ORDER BY id ASC LIMIT 1', [$language]);
                }

                if (false === $row) {
                    $row = $this->db->fetchAssociative('SELECT * FROM tl_neo_websitecheck_config ORDER BY id ASC LIMIT 1');
                }
            } catch (DbalException) {
                $row = false;
            }

            $this->texts[$key] = false === $row ? null : $row;
        }

        return $this->texts[$key];
    }

    /** A trimmed column of the applicable configuration ('' when none). */
    public function configValue(int $id, string $language, string $column): string
    {
        return trim((string) ($this->config($id, $language)[$column] ?? ''));
    }

    /** @return list<array{id: int, title: string, language: string}> */
    public function configs(): array
    {
        try {
            $rows = $this->db->fetchAllAssociative('SELECT id, title, language FROM tl_neo_websitecheck_config ORDER BY language, title');
        } catch (DbalException) {
            return [];
        }

        return array_map(static fn (array $r): array => ['id' => (int) $r['id'], 'title' => (string) $r['title'], 'language' => (string) $r['language']], $rows);
    }

    /** @deprecated use configValue(); kept for templates/extensions written against 1.0 */
    public function text(string $language, string $column): string
    {
        return $this->configValue(0, $language, $column);
    }

    public function reset(): void
    {
        $this->row = false;
        $this->texts = [];
    }
}
