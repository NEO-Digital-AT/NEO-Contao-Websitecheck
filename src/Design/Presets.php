<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Design;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Ready-made designs (resources/presets/{pdf|mail}/<name>.json). Text
 * properties in a preset reference translation keys ("@report.cover_title"),
 * so the same preset yields German, English … designs; load() resolves them
 * for the requested locale.
 */
final class Presets
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /** @return array<string, string> name => label */
    public function available(string $kind, string $locale): array
    {
        $out = [];

        foreach (glob($this->dir($kind).'/*.json') ?: [] as $file) {
            $name = basename($file, '.json');
            $label = $this->translator->trans('designer.preset.'.$kind.'.'.$name, [], 'neo_websitecheck', $locale);
            $out[$name] = $label === 'designer.preset.'.$kind.'.'.$name ? ucfirst($name) : $label;
        }

        ksort($out);

        // "standard" first: it is what a new configuration starts with
        if (isset($out['standard'])) {
            $out = ['standard' => $out['standard']] + $out;
        }

        return $out;
    }

    /**
     * @return array<string, mixed>|null normalised design or null when unknown
     */
    public function load(string $kind, string $name, string $locale): array|null
    {
        $file = $this->dir($kind).'/'.preg_replace('/[^a-z0-9_-]/', '', $name).'.json';

        if (!is_file($file)) {
            return null;
        }

        $raw = json_decode((string) file_get_contents($file), true);

        if (!\is_array($raw)) {
            return null;
        }

        return Design::normalise($this->resolve($raw, $locale), $kind);
    }

    public function default(string $kind, string $locale): array
    {
        return $this->load($kind, 'standard', $locale) ?? Design::normalise([], $kind);
    }

    private function resolve(mixed $value, string $locale): mixed
    {
        if (\is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->resolve($v, $locale);
            }

            return $value;
        }

        if (\is_string($value) && str_contains($value, '@')) {
            // "@report.title" as a whole value or embedded in HTML ("<p>@mail.intro</p>")
            return (string) preg_replace_callback('/(?<=^|>|\s)@([a-z][a-z0-9_]*(?:\.[a-z0-9_]+)+)/i', function (array $m) use ($locale): string {
                $translated = $this->translator->trans($m[1], [], 'neo_websitecheck', $locale);

                return $translated === $m[1] ? '' : $translated;
            }, $value);
        }

        return $value;
    }

    private function dir(string $kind): string
    {
        return \dirname(__DIR__, 2).'/resources/presets/'.(BlockRegistry::KIND_MAIL === $kind ? 'mail' : 'pdf');
    }
}
