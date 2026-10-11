<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Text;

use Neo\WebsitecheckBundle\Check\SettingsRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Customer-facing texts: the operator's override from the applicable
 * tl_neo_websitecheck_config record (vars['config_id'], else the record of
 * the language) when set, otherwise the bundle translation. Placeholders such
 * as {{firstname}} or {{domain}} are replaced in both. The designed e-mail and
 * PDF carry their texts inside the design; KEYS covers subjects and the
 * front-end element.
 */
final class TextProvider
{
    /** Placeholders available in every editable text (documented in the back end). */
    public const PLACEHOLDERS = ['firstname', 'lastname', 'domain', 'grade', 'grade_word', 'pages', 'company', 'owner', 'phone', 'website', 'days', 'date'];

    /**
     * Editable keys: translation key → column, long (textarea) flag and the
     * mapping from translation parameters to placeholder names.
     *
     * @var array<string, array{column: string, long: bool, params: array<string, string>}>
     */
    public const KEYS = [
        'mail.subject' => ['column' => 'mail_subject', 'long' => false, 'params' => ['%domain%' => 'domain', '%grade%' => 'grade', '%word%' => 'grade_word']],
        'mail.owner_subject' => ['column' => 'mail_owner_subject', 'long' => false, 'params' => ['%domain%' => 'domain', '%grade%' => 'grade', '%name%' => 'firstname', '%newsletter%' => 'newsletter']],
        'fe.url_label' => ['column' => 'fe_url_label', 'long' => false, 'params' => []],
        'fe.submit' => ['column' => 'fe_submit', 'long' => false, 'params' => []],
        'fe.gate_title' => ['column' => 'fe_gate_title', 'long' => false, 'params' => []],
        'fe.gate_text' => ['column' => 'fe_gate_text', 'long' => true, 'params' => []],
        'fe.gate_legal' => ['column' => 'fe_gate_legal', 'long' => true, 'params' => []],
        'fe.contact' => ['column' => 'fe_contact', 'long' => true, 'params' => []],
        'fe.newsletter' => ['column' => 'fe_newsletter', 'long' => true, 'params' => []],
        'fe.gate_submit' => ['column' => 'fe_gate_submit', 'long' => false, 'params' => []],
        'fe.disclaimer' => ['column' => 'fe_disclaimer', 'long' => true, 'params' => []],
        'fe.cta' => ['column' => 'fe_cta', 'long' => false, 'params' => []],
    ];

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly SettingsRepository $settings,
    ) {
    }

    /**
     * @return list<string> column names of one group (report|mail|fe)
     */
    public static function columns(string $group): array
    {
        $out = [];

        foreach (self::KEYS as $key => $def) {
            if (str_starts_with($key, $group.'.')) {
                $out[] = $def['column'];
            }
        }

        return $out;
    }

    public static function isEditable(string $key): bool
    {
        return isset(self::KEYS[$key]);
    }

    /**
     * @param array<string, scalar|null> $vars placeholder values (see PLACEHOLDERS)
     */
    public function get(string $key, string $locale, array $vars = []): string
    {
        $def = self::KEYS[$key] ?? null;

        if (null !== $def) {
            $custom = $this->settings->configValue((int) ($vars['config_id'] ?? 0), $locale, $def['column']);

            if ('' !== $custom) {
                return $this->replace($custom, $vars);
            }
        }

        $params = [];

        foreach ($def['params'] ?? [] as $param => $var) {
            $params[$param] = (string) ($vars[$var] ?? '');
        }

        // Keys outside KEYS may pass translation params directly as %name%.
        foreach ($vars as $k => $v) {
            if (str_starts_with((string) $k, '%')) {
                $params[$k] = (string) $v;
            }
        }

        return $this->replace($this->translator->trans($key, $params, 'neo_websitecheck', $locale), $vars);
    }

    /**
     * @param array<string, scalar|null> $vars
     */
    private function replace(string $text, array $vars): string
    {
        if (!str_contains($text, '{{')) {
            return $text;
        }

        return (string) preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/', static fn (array $m): string => (string) ($vars[$m[1]] ?? ''), $text);
    }
}
