<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Backend;

use Contao\Backend;
use Contao\DataContainer;
use Contao\Input;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Neo\WebsitecheckBundle\Text\TextProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment;

/**
 * DCA callbacks of the settings module (public service, resolved by class name).
 */
final class SettingsCallbacks
{
    public function __construct(
        private readonly Connection $db,
        private readonly Environment $twig,
        private readonly Security $security,
    ) {
    }

    /**
     * New language record: start from the complete templates (the effective
     * ones, i.e. a project override wins over the bundle file) and a sensible
     * file name, so "fully individual" means editing, not writing from scratch.
     *
     * @param array<string, mixed> $set
     */
    public function prefillTemplates(string $table, int $insertId, array $set): void
    {
        $update = ['pdf_filename' => 'website-check-{{domain}}-{{date}}.pdf'];

        foreach (['mail_body' => 'mail_visitor', 'owner_body' => 'mail_owner', 'pdf_body' => 'report'] as $column => $template) {
            try {
                $update[$column] = $this->twig->getLoader()->getSourceContext('@Contao/neo_websitecheck/'.$template.'.html.twig')->getCode();
            } catch (\Throwable) {
                // keep empty → bundle template
            }
        }

        $this->db->update('tl_neo_websitecheck_text', $update, ['id' => $insertId]);
    }

    /**
     * The complete templates are Twig code: whoever edits them can run
     * template logic, so — like Contao's own template editor — only
     * administrators get these fields; editors keep the snippets.
     */
    public function restrictTemplatesToAdmins(): void
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        foreach (['mail_body', 'owner_body', 'pdf_body'] as $field) {
            unset($GLOBALS['TL_DCA']['tl_neo_websitecheck_text']['fields'][$field]);
        }

        $palette = (string) $GLOBALS['TL_DCA']['tl_neo_websitecheck_text']['palettes']['default'];
        $palette = str_replace(['{mail_body_legend:hide},mail_body;', ',owner_body', '{pdf_body_legend:hide},pdf_body;'], ['', '', ''], $palette);
        $GLOBALS['TL_DCA']['tl_neo_websitecheck_text']['palettes']['default'] = $palette;
    }

    /**
     * Preview links in the language record (preview in that language).
     *
     * @param array<string, string> $buttons
     *
     * @return array<string, string>
     */
    public function addPreviewButtonsForLanguage(array $buttons, DataContainer $dc): array
    {
        $lang = $GLOBALS['TL_LANG']['tl_neo_websitecheck_settings'];
        $language = (string) $this->db->fetchOne('SELECT language FROM tl_neo_websitecheck_text WHERE id = ?', [(int) $dc->id]);

        foreach (['preview_pdf', 'preview_mail'] as $key) {
            $buttons[$key] = sprintf('<a href="%s" class="tl_submit" target="_blank" rel="noopener">%s</a>', Backend::addToUrl('key='.$key.'&lang='.$language, true, ['act', 'id', 'table']), StringUtil::specialchars($lang[$key][0] ?? $key));
        }

        return $buttons;
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
