<?php

declare(strict_types=1);

use Contao\DataContainer;
use Contao\DC_Table;
use Neo\WebsitecheckBundle\Backend\SettingsCallbacks;
use Neo\WebsitecheckBundle\Text\TextProvider;

/*
 * tl_neo_websitecheck_config — one configuration per language (or per use
 * case): the complete e-mail and PDF design (visual designer, stored as JSON),
 * subject, sender, PDF file name and the texts of the front-end element.
 * The content element picks a configuration or, when left on "automatic",
 * the one whose language matches the page. Experts may replace the designed
 * e-mail/PDF by a Twig template (expert legend, administrators only).
 */
$fields = [
    'id' => ['sql' => 'int(10) unsigned NOT NULL auto_increment'],
    'pid' => ['foreignKey' => 'tl_neo_websitecheck_settings.id', 'sql' => 'int(10) unsigned NOT NULL default 0', 'relation' => ['type' => 'belongsTo', 'load' => 'lazy']],
    'tstamp' => ['sql' => 'int(10) unsigned NOT NULL default 0'],
    'title' => [
        'inputType' => 'text',
        'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
        'sql' => "varchar(255) NOT NULL default ''",
    ],
    'language' => [
        'inputType' => 'select',
        'options_callback' => [SettingsCallbacks::class, 'languageOptions'],
        'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
        'sql' => "varchar(5) NOT NULL default ''",
    ],
    // --- e-mail ------------------------------------------------------------
    'mail_subject' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'long clr'], 'sql' => "varchar(255) NOT NULL default ''"],
    'mail_from_name' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'w50'], 'sql' => "varchar(255) NOT NULL default ''"],
    'mail_from_email' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'rgxp' => 'email', 'decodeEntities' => true, 'tl_class' => 'w50'], 'sql' => "varchar(255) NOT NULL default ''"],
    'mail_reply_to' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'rgxp' => 'email', 'decodeEntities' => true, 'tl_class' => 'w50'], 'sql' => "varchar(255) NOT NULL default ''"],
    'mail_owner_subject' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'], 'sql' => "varchar(255) NOT NULL default ''"],
    'mail_design' => ['inputType' => 'neoDesigner', 'eval' => ['kind' => 'mail', 'tl_class' => 'clr'], 'sql' => 'mediumtext NULL'],
    // --- PDF ---------------------------------------------------------------
    'pdf_filename' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'long clr'], 'sql' => "varchar(255) NOT NULL default ''"],
    'pdf_design' => ['inputType' => 'neoDesigner', 'eval' => ['kind' => 'pdf', 'tl_class' => 'clr'], 'sql' => 'mediumtext NULL'],
    // --- advanced mode: complete Twig templates (administrators only, see onload).
    // mail_body/pdf_body are edited inside the designer (switch "Advanced (Twig)"); owner_body is the internal copy.
    'mail_body' => ['inputType' => 'textarea', 'save_callback' => [[SettingsCallbacks::class, 'validateTwig']], 'eval' => ['rte' => 'ace|twig', 'allowHtml' => true, 'preserveTags' => true, 'decodeEntities' => true, 'class' => 'monospace', 'rows' => 24, 'tl_class' => 'clr'], 'sql' => 'mediumtext NULL'],
    'owner_body' => ['inputType' => 'textarea', 'save_callback' => [[SettingsCallbacks::class, 'validateTwig']], 'eval' => ['rte' => 'ace|twig', 'allowHtml' => true, 'preserveTags' => true, 'decodeEntities' => true, 'class' => 'monospace', 'rows' => 24, 'tl_class' => 'clr'], 'sql' => 'mediumtext NULL'],
    'pdf_body' => ['inputType' => 'textarea', 'save_callback' => [[SettingsCallbacks::class, 'validateTwig']], 'eval' => ['rte' => 'ace|twig', 'allowHtml' => true, 'preserveTags' => true, 'decodeEntities' => true, 'class' => 'monospace', 'rows' => 24, 'tl_class' => 'clr'], 'sql' => 'mediumtext NULL'],
];

// Texts of the front-end element (short snippets, placeholders allowed).
foreach (TextProvider::KEYS as $key => $def) {
    if (!str_starts_with($key, 'fe.')) {
        continue;
    }

    $fields[$def['column']] = [
        'inputType' => $def['long'] ? 'textarea' : 'text',
        'eval' => ['tl_class' => $def['long'] ? 'clr' : 'w50', 'decodeEntities' => true] + ($def['long'] ? ['rows' => 3] : ['maxlength' => 255]),
        'sql' => $def['long'] ? 'text NULL' : "varchar(255) NOT NULL default ''",
    ];
}

$GLOBALS['TL_DCA']['tl_neo_websitecheck_config'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_neo_websitecheck_settings',
        'enableVersioning' => true,
        'onload_callback' => [[SettingsCallbacks::class, 'restrictTemplatesToAdmins']],
        'oncreate_callback' => [[SettingsCallbacks::class, 'prefillConfig']],
        'onsubmit_callback' => [[SettingsCallbacks::class, 'fillEmptyDesigns']],
        'sql' => ['keys' => ['id' => 'primary', 'pid,language' => 'index']],
    ],
    'edit' => [
        'buttons_callback' => [[SettingsCallbacks::class, 'addPreviewButtonsForConfig']],
    ],
    'list' => [
        'sorting' => ['mode' => DataContainer::MODE_PARENT, 'fields' => ['language', 'title'], 'headerFields' => ['brand_name'], 'panelLayout' => '', 'child_record_callback' => [SettingsCallbacks::class, 'configRecordLabel']],
        'operations' => [
            'edit' => ['href' => 'act=edit', 'icon' => 'edit.svg'],
            'copy' => ['href' => 'act=copy', 'icon' => 'copy.svg'],
            'delete' => ['href' => 'act=delete', 'icon' => 'delete.svg', 'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '').'\'))return false;Backend.getScrollOffset()"'],
            'show' => ['href' => 'act=show', 'icon' => 'show.svg'],
        ],
    ],
    'palettes' => [
        'default' => '{title_legend},title,language;'
            .'{mail_legend},mail_subject,mail_from_name,mail_from_email,mail_reply_to;'
            .'{mail_design_legend},mail_design,mail_body;'
            .'{pdf_legend},pdf_filename;'
            .'{pdf_design_legend},pdf_design,pdf_body;'
            .'{owner_legend:hide},mail_owner_subject;'
            .'{element_legend:hide},'.implode(',', TextProvider::columns('fe')).';'
            .'{expert_legend:hide},owner_body',
    ],
    'fields' => $fields,
];
