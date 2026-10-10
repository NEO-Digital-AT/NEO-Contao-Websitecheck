<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Backend;

use Contao\Backend;
use Contao\DataContainer;
use Contao\Input;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Neo\WebsitecheckBundle\Design\Presets;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * DCA callbacks of the settings module (public service, resolved by class name).
 */
final class SettingsCallbacks
{
    public function __construct(
        private readonly Connection $db,
        private readonly Security $security,
        private readonly Presets $presets,
    ) {
    }

    /**
     * New configuration record: a sensible PDF file name. The designs are
     * filled on the first save (fillEmptyDesigns), when the language is known.
     *
     * @param array<string, mixed> $set
     */
    public function prefillConfig(string $table, int $insertId, array $set): void
    {
        $this->db->update('tl_neo_websitecheck_config', ['pdf_filename' => 'website-check-{{domain}}-{{date}}.pdf'], ['id' => $insertId]);
    }

    /**
     * Empty designs get the "standard" preset in the record's language, so a
     * freshly saved configuration already produces the complete e-mail and PDF.
     */
    public function fillEmptyDesigns(DataContainer $dc): void
    {
        $row = $this->db->fetchAssociative('SELECT language, mail_design, pdf_design FROM tl_neo_websitecheck_config WHERE id = ?', [(int) $dc->id]);

        if (false === $row) {
            return;
        }

        $locale = (string) ($row['language'] ?: 'en');
        $update = [];

        foreach (['mail_design' => 'mail', 'pdf_design' => 'pdf'] as $column => $kind) {
            if ('' === trim((string) $row[$column])) {
                $update[$column] = json_encode($this->presets->default($kind, $locale), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        if ([] !== $update) {
            $this->db->update('tl_neo_websitecheck_config', $update, ['id' => (int) $dc->id]);
        }
    }

    /**
     * The expert Twig templates can run template logic, so — like Contao's own
     * template editor — only administrators get these fields.
     */
    public function restrictTemplatesToAdmins(): void
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        foreach (['mail_body', 'owner_body', 'pdf_body'] as $field) {
            unset($GLOBALS['TL_DCA']['tl_neo_websitecheck_config']['fields'][$field]);
        }

        $palette = (string) $GLOBALS['TL_DCA']['tl_neo_websitecheck_config']['palettes']['default'];
        $GLOBALS['TL_DCA']['tl_neo_websitecheck_config']['palettes']['default'] = str_replace(';{expert_legend:hide},mail_body,owner_body,pdf_body', '', $palette);
    }

    /**
     * Preview links in the configuration record (that configuration, its language).
     *
     * @param array<string, string> $buttons
     *
     * @return array<string, string>
     */
    public function addPreviewButtonsForConfig(array $buttons, DataContainer $dc): array
    {
        $lang = $GLOBALS['TL_LANG']['tl_neo_websitecheck_config'];
        $row = $this->db->fetchAssociative('SELECT id, language FROM tl_neo_websitecheck_config WHERE id = ?', [(int) $dc->id]);

        if (false === $row) {
            return $buttons;
        }

        foreach (['preview_pdf', 'preview_mail'] as $key) {
            $buttons[$key] = sprintf('<a href="%s" class="tl_submit" target="_blank" rel="noopener">%s</a>', Backend::addToUrl('key='.$key.'&lang='.$row['language'].'&config='.(int) $row['id'], true, ['act', 'id', 'table']), StringUtil::specialchars($lang[$key][0] ?? $key));
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

    /** "Configurations (N)" block inside the settings mask (they live in the child table). */
    public function textsInfo(DataContainer $dc): string
    {
        $lang = $GLOBALS['TL_LANG']['tl_neo_websitecheck_settings'];
        $rows = $this->db->fetchAllAssociative('SELECT title, language FROM tl_neo_websitecheck_config WHERE pid = ? ORDER BY language, title', [(int) $dc->id]);
        $languages = array_map(static fn (array $r): string => $r['title'].' ['.strtoupper((string) $r['language']).']', $rows);
        $href = Backend::addToUrl('table=tl_neo_websitecheck_config', true, ['act']);

        $info = [] === $languages
            ? ($lang['textsNone'] ?? '')
            : sprintf($lang['textsList'] ?? '%s', implode(', ', $languages));

        return sprintf(
            '<div class="widget clr"><p class="tl_help tl_tip">%s</p><a href="%s" class="tl_submit">%s</a></div>',
            StringUtil::specialchars($info),
            $href,
            StringUtil::specialchars(sprintf($lang['textsButton'] ?? 'Texts (%d)', \count($languages))),
        );
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
    public function configRecordLabel(array $row): string
    {
        $designed = ('' !== trim((string) $row['mail_design']) ? 1 : 0) + ('' !== trim((string) $row['pdf_design']) ? 1 : 0);
        $expert = '' !== trim((string) $row['mail_body']) || '' !== trim((string) $row['pdf_body']);

        return sprintf('<div class="tl_content_left"><strong>%s</strong> <span style="color:#999;padding-left:3px">%s · %s%s</span></div>', StringUtil::specialchars((string) $row['title']), strtoupper((string) $row['language']), $designed.'/2', $expert ? ' · Twig' : '');
    }
}
