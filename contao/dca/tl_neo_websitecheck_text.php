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

$GLOBALS['TL_DCA']['tl_neo_websitecheck_text'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_neo_websitecheck_settings',
        'enableVersioning' => true,
        'sql' => ['keys' => ['id' => 'primary', 'pid,language' => 'index']],
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
            .'{report_legend},'.implode(',', TextProvider::columns('report')).';'
            .'{mail_legend},'.implode(',', TextProvider::columns('mail')).';'
            .'{element_legend},'.implode(',', TextProvider::columns('fe')),
    ],
    'fields' => $fields + [
        'placeholders_info' => ['input_field_callback' => [SettingsCallbacks::class, 'placeholdersInfo']],
    ],
];

unset($fields);
