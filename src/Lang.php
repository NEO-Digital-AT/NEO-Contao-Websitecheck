<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle;

/**
 * Language handling without any language as a special case: configurations,
 * cached checks and catalogues are matched by the primary language subtag of a
 * locale ("de" for de-AT, "pt" for pt_BR, "fil" for fil-PH).
 */
final class Lang
{
    public static function primary(string $locale): string
    {
        $primary = strtolower((string) strtok(str_replace('_', '-', trim($locale)), '-'));

        return 1 === preg_match('/^[a-z]{2,3}$/', $primary) ? $primary : '';
    }

    /** English name of a language for AI prompts ("French", "Brazilian Portuguese"); the code when unknown. */
    public static function englishName(string $locale): string
    {
        $code = str_replace('-', '_', trim($locale));

        if ('' !== $code && class_exists(\Locale::class)) {
            $name = \Locale::getDisplayName($code, 'en');

            if ('' !== $name && strtolower($name) !== strtolower($code)) {
                return $name;
            }
        }

        return '' !== $code ? $code : 'the language of the website';
    }
}
