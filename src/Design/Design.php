<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Design;

use Twig\Markup;

/**
 * A stored design (JSON from the back-end designer): theme settings plus an
 * ordered list of blocks. normalise() validates everything against the
 * BlockRegistry (unknown blocks and properties are dropped, missing properties
 * get their defaults, HTML is reduced to a safe subset), prepare() additionally
 * fills the {{placeholders}} for rendering.
 */
final class Design
{
    public const VERSION = 1;

    /** Tags an editor may use in rich-text properties (everything else is stripped). */
    private const ALLOWED_TAGS = '<p><br><b><strong><i><em><u><a><ul><ol><li><span><small><h1><h2><h3><h4>';

    /**
     * @param array<string, mixed>|string|null $raw
     *
     * @return array{v: int, theme: array<string, mixed>, blocks: list<array{id: string, type: string, props: array<string, mixed>}>}
     */
    public static function normalise(array|string|null $raw, string $kind): array
    {
        if (\is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = \is_array($decoded) ? $decoded : [];
        }

        $raw ??= [];
        $theme = [];

        foreach (BlockRegistry::theme($kind) as $name => $prop) {
            $theme[$name] = self::value($prop, $raw['theme'][$name] ?? null);
        }

        $blocks = [];
        $definitions = BlockRegistry::blocks($kind);

        foreach ((array) ($raw['blocks'] ?? []) as $i => $block) {
            if (!\is_array($block) || !isset($definitions[$block['type'] ?? ''])) {
                continue;
            }

            $type = (string) $block['type'];
            $props = [];

            foreach ($definitions[$type]['props'] as $name => $prop) {
                $props[$name] = self::value($prop, $block['props'][$name] ?? null);
            }

            $id = preg_replace('/[^a-z0-9_-]/i', '', (string) ($block['id'] ?? ''));
            $blocks[] = ['id' => '' !== $id ? $id : 'b'.($i + 1), 'type' => $type, 'props' => $props];
        }

        return ['v' => self::VERSION, 'theme' => $theme, 'blocks' => $blocks];
    }

    /**
     * Normalised design with every text filled: placeholders replaced (values
     * escaped), rich text wrapped as Markup so templates can print it as is.
     *
     * @param array<string, mixed>|string|null $raw
     * @param array<string, scalar|null>       $vars
     *
     * @return array{v: int, theme: array<string, mixed>, blocks: list<array{id: string, type: string, props: array<string, mixed>}>}
     */
    public static function prepare(array|string|null $raw, string $kind, array $vars): array
    {
        $design = self::normalise($raw, $kind);
        $definitions = BlockRegistry::blocks($kind);
        $themeDefs = BlockRegistry::theme($kind);

        foreach ($design['theme'] as $name => $value) {
            if (\in_array($themeDefs[$name]['type'], ['text', 'textarea'], true)) {
                $design['theme'][$name] = self::fill((string) $value, $vars, false);
            }
        }

        foreach ($design['blocks'] as &$block) {
            foreach ($block['props'] as $name => $value) {
                $type = $definitions[$block['type']]['props'][$name]['type'];

                if ('richtext' === $type) {
                    $block['props'][$name] = new Markup(self::fill((string) $value, $vars, true), 'UTF-8');
                } elseif (\in_array($type, ['text', 'textarea'], true)) {
                    $block['props'][$name] = self::fill((string) $value, $vars, false);
                }
            }
        }

        unset($block);

        return $design;
    }

    /**
     * Replaces {{placeholder}} tokens. In HTML context the values are escaped
     * (the surrounding markup is already sanitised); in plain context the
     * template escapes the whole string later.
     *
     * @param array<string, scalar|null> $vars
     */
    public static function fill(string $text, array $vars, bool $html): string
    {
        if (!str_contains($text, '{{')) {
            return $text;
        }

        return (string) preg_replace_callback(
            '/\{\{\s*([a-z_]+)\s*\}\}/',
            static function (array $m) use ($vars, $html): string {
                $value = (string) ($vars[$m[1]] ?? '');

                return $html ? htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : $value;
            },
            $text,
        );
    }

    /** Reduces editor HTML to a safe subset (tags whitelist, no event handlers, http(s)/mailto links only). */
    public static function sanitize(string $html): string
    {
        $html = strip_tags($html, self::ALLOWED_TAGS);
        // drop every attribute except href on <a>
        $html = (string) preg_replace_callback('/<(\w+)([^>]*)>/', static function (array $m): string {
            $tag = strtolower($m[1]);

            if ('a' === $tag && preg_match('/href\s*=\s*(["\'])(.*?)\1/i', $m[2], $h)) {
                $href = trim(html_entity_decode($h[2]));

                if (preg_match('#^(https?://|mailto:|\{\{)#i', $href)) {
                    return '<a href="'.htmlspecialchars($href, ENT_QUOTES).'">';
                }
            }

            return '<'.$tag.'>';
        }, $html);

        return trim($html);
    }

    /**
     * @param array<string, mixed> $prop
     */
    private static function value(array $prop, mixed $raw): mixed
    {
        $default = $prop['default'] ?? null;

        switch ($prop['type']) {
            case 'bool':
                return null === $raw ? (bool) $default : \in_array($raw, [true, 1, '1', 'true', 'on'], true);
            case 'number':
                $n = null === $raw || '' === $raw ? (float) $default : (float) $raw;
                $n = max((float) ($prop['min'] ?? -INF), min((float) ($prop['max'] ?? INF), $n));

                return (float) (int) $n === $n ? (int) $n : $n;
            case 'select':
                return \in_array($raw, $prop['options'], true) ? $raw : $default;
            case 'align':
                return \in_array($raw, ['left', 'center', 'right'], true) ? $raw : $default;
            case 'color':
                $v = ltrim(trim((string) $raw), '#');

                return preg_match('/^[0-9a-fA-F]{6}$/', $v) ? '#'.strtolower($v) : (string) $default;
            case 'image':
                $v = trim((string) $raw);
                // relative project path (files/…) or a UUID string; nothing else
                return preg_match('#^(files/[^\s"\'<>]+|[0-9a-f-]{36})$#i', $v) ? $v : '';
            case 'richtext':
                return null === $raw ? (string) $default : self::sanitize((string) $raw);
            case 'textarea':
                return null === $raw ? (string) $default : trim(strip_tags((string) $raw));
            default: // text
                return null === $raw ? (string) $default : trim(strip_tags(str_replace(["\r", "\n"], ' ', (string) $raw)));
        }
    }
}
