<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Backend;

use Contao\Backend;
use Contao\DataContainer;
use Contao\Input;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Neo\WebsitecheckBundle\Text\TextProvider;

/**
 * DCA callbacks of the settings module (public service, resolved by class name).
 */
final class SettingsCallbacks
{
    public function __construct(private readonly Connection $db)
    {
    }

    /** Creates the single settings row on the first back-end visit. */
    public function ensureRow(): void
    {
        if ((int) $this->db->fetchOne('SELECT COUNT(*) FROM tl_neo_websitecheck_settings') > 0) {
            return;
        }

        $this->db->insert('tl_neo_websitecheck_settings', ['tstamp' => time()]);
    }

    /** One record only: skip the list and open the edit mask (trail URLs with table= pass through). */
    public function redirectToEditView(): void
    {
        if (Input::get('act') || Input::get('key') || Input::get('table')) {
            return;
        }

        $id = $this->db->fetchOne('SELECT id FROM tl_neo_websitecheck_settings ORDER BY id ASC LIMIT 1');

        if (false === $id) {
            return;
        }

        Backend::redirect(Backend::addToUrl('act=edit&id='.(int) $id));
    }

    /**
     * Preview links next to the save buttons of the edit mask.
     *
     * @param array<string, string> $buttons
     *
     * @return array<string, string>
     */
    public function addPreviewButtons(array $buttons): array
    {
        $lang = $GLOBALS['TL_LANG']['tl_neo_websitecheck_settings'];

        foreach (['preview_pdf', 'preview_mail'] as $key) {
            $buttons[$key] = sprintf('<a href="%s" class="tl_submit" target="_blank" rel="noopener">%s</a>', Backend::addToUrl('key='.$key, true, ['act', 'id']), StringUtil::specialchars($lang[$key][0] ?? $key));
        }

        return $buttons;
    }

    /** "Edit texts (N)" block inside the settings mask (the texts live in the child table). */
    public function textsInfo(DataContainer $dc): string
    {
        $lang = $GLOBALS['TL_LANG']['tl_neo_websitecheck_settings'];
        $rows = $this->db->fetchAllAssociative('SELECT language FROM tl_neo_websitecheck_text WHERE pid = ? ORDER BY language', [(int) $dc->id]);
        $languages = array_column($rows, 'language');
        $href = Backend::addToUrl('table=tl_neo_websitecheck_text', true, ['act', 'id']);

        $info = [] === $languages
            ? ($lang['textsNone'] ?? '')
            : sprintf($lang['textsList'] ?? '%s', implode(', ', array_map('strtoupper', $languages)));

        return sprintf(
            '<div class="widget clr"><p class="tl_help tl_tip">%s</p><a href="%s" class="tl_submit">%s</a></div>',
            StringUtil::specialchars($info),
            $href,
            StringUtil::specialchars(sprintf($lang['textsButton'] ?? 'Texts (%d)', \count($languages))),
        );
    }

    /** Placeholder cheat-sheet at the top of a texts record. */
    public function placeholdersInfo(): string
    {
        $text = $GLOBALS['TL_LANG']['tl_neo_websitecheck_text']['placeholdersText'] ?? '%s';
        $list = implode(', ', array_map(static fn (string $p): string => '<code>{{'.$p.'}}</code>', TextProvider::PLACEHOLDERS));

        return '<div class="widget clr"><p class="tl_help tl_tip">'.sprintf(StringUtil::specialchars($text), $list).'</p></div>';
    }

    /**
     * Languages of the published root pages (plus en/de as a fallback).
     *
     * @return array<string, string>
     */
    public function languageOptions(): array
    {
        $languages = ['de' => 'Deutsch', 'en' => 'English'];

        try {
            foreach ($this->db->fetchFirstColumn("SELECT DISTINCT language FROM tl_page WHERE type = 'root'") as $language) {
                $code = substr((string) $language, 0, 2);

                if ('' !== $code && !isset($languages[$code])) {
                    $languages[$code] = strtoupper($code);
                }
            }
        } catch (\Throwable) {
            // tl_page not available (should not happen inside Contao)
        }

        return $languages;
    }

    /**
     * @param array<string, mixed> $row
     */
    public function textRecordLabel(array $row): string
    {
        $filled = 0;

        foreach (TextProvider::KEYS as $def) {
            if ('' !== trim((string) ($row[$def['column']] ?? ''))) {
                ++$filled;
            }
        }

        return sprintf('<div class="tl_content_left"><strong>%s</strong> <span style="color:#999;padding-left:3px">%d / %d</span></div>', strtoupper((string) $row['language']), $filled, \count(TextProvider::KEYS));
    }
}
