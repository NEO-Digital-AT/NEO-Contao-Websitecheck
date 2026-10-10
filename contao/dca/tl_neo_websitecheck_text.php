<?php

declare(strict_types=1);

use Contao\DataContainer;
use Contao\DC_Table;
use Neo\WebsitecheckBundle\Backend\SettingsCallbacks;
use Neo\WebsitecheckBundle\Text\TextProvider;

/*
 * tl_neo_websitecheck_text — the customer-facing texts of the website check,
 * one record per language. Every field is optional: an empty field keeps the
 * bundle's translated default, so an operator only overrides what should sound
 * like their own voice. Placeholders (see TextProvider::PLACEHOLDERS) are
 * replaced when the text is rendered: {{firstname}}, {{domain}}, {{grade}} …
 */
$fields = [
    'id' => ['sql' => 'int(10) unsigned NOT NULL auto_increment'],
    'pid' => ['foreignKey' => 'tl_neo_websitecheck_settings.id', 'sql' => 'int(10) unsigned NOT NULL default 0', 'relation' => ['type' => 'belongsTo', 'load' => 'lazy']],
    'tstamp' => ['sql' => 'int(10) unsigned NOT NULL default 0'],
    'language' => [
        'inputType' => 'select',
        'options_callback' => [SettingsCallbacks::class, 'languageOptions'],
        'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
        'sql' => "varchar(5) NOT NULL default ''",
    ],
];

foreach (TextProvider::KEYS as $key => $def) {
    $column = $def['column'];
    $fields[$column] = [
        'inputType' => $def['long'] ? 'textarea' : 'text',
        'eval' => ['tl_class' => $def['long'] ? 'clr' : 'w50', 'decodeEntities' => true] + ($def['long'] ? ['rows' => 3] : ['maxlength' => 255]),
        'sql' => $def['long'] ? 'text NULL' : "varchar(255) NOT NULL default ''",
    ];
}

// Full templates (Twig/HTML) and per-language sender/file name. Prefilled with
// the bundle (or project override) template when a language record is created,
// so editing starts from the complete design; an emptied field falls back to it.
$code = ['inputType' => 'textarea', 'eval' => ['rte' => 'ace|html', 'allowHtml' => true, 'preserveTags' => true, 'decodeEntities' => true, 'class' => 'monospace', 'rows' => 24, 'tl_class' => 'clr'], 'sql' => 'mediumtext NULL'];
$fields['mail_from_name'] = ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'w50'], 'sql' => "varchar(255) NOT NULL default ''"];
$fields['mail_from_email'] = ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'rgxp' => 'email', 'decodeEntities' => true, 'tl_class' => 'w50'], 'sql' => "varchar(255) NOT NULL default ''"];
$fields['mail_reply_to'] = ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'rgxp' => 'email', 'decodeEntities' => true, 'tl_class' => 'w50'], 'sql' => "varchar(255) NOT NULL default ''"];
$fields['mail_body'] = $code;
$fields['owner_body'] = $code;
$fields['pdf_filename'] = ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'], 'sql' => "varchar(255) NOT NULL default ''"];
$fields['pdf_body'] = $code;

$GLOBALS['TL_DCA']['tl_neo_websitecheck_text'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_neo_websitecheck_settings',
        'enableVersioning' => true,
        'onload_callback' => [[SettingsCallbacks::class, 'restrictTemplatesToAdmins']],
        'oncreate_callback' => [[SettingsCallbacks::class, 'prefillTemplates']],
        'sql' => ['keys' => ['id' => 'primary', 'pid,language' => 'index']],
    ],
    'edit' => [
        'buttons_callback' => [[SettingsCallbacks::class, 'addPreviewButtonsForLanguage']],
    ],
    'list' => [
        'sorting' => ['mode' => DataContainer::MODE_PARENT, 'fields' => ['language'], 'headerFields' => ['brand_name'], 'panelLayout' => '', 'child_record_callback' => [SettingsCallbacks::class, 'textRecordLabel']],
        'operations' => [
            'edit' => ['href' => 'act=edit', 'icon' => 'edit.svg'],
            'copy' => ['href' => 'act=copy', 'icon' => 'copy.svg'],
            'delete' => ['href' => 'act=delete', 'icon' => 'delete.svg', 'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '').'\'))return false;Backend.getScrollOffset()"'],
            'show' => ['href' => 'act=show', 'icon' => 'show.svg'],
        ],
    ],
    'palettes' => [
        'default' => '{language_legend},language;'
            .'{placeholders_legend},placeholders_info;'
            .'{mail_legend},mail_subject,mail_from_name,mail_from_email,mail_reply_to,'.implode(',', array_diff(TextProvider::columns('mail'), ['mail_subject', 'mail_owner_subject'])).';'
            .'{mail_body_legend:hide},mail_body;'
            .'{owner_legend:hide},mail_owner_subject,owner_body;'
            .'{pdf_legend},pdf_filename,'.implode(',', TextProvider::columns('report')).';'
            .'{pdf_body_legend:hide},pdf_body;'
            .'{element_legend},'.implode(',', TextProvider::columns('fe')),
    ],
    'fields' => $fields + [
        'placeholders_info' => ['input_field_callback' => [SettingsCallbacks::class, 'placeholdersInfo']],
    ],
];

unset($fields, $code);
