<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Backend;

use Contao\DataContainer;
use Contao\Input;
use Contao\System;
use Doctrine\DBAL\Connection;
use Neo\WebsitecheckBundle\Check\SettingsRepository;

/**
 * tl_content callbacks of the "Website check" element (public service, resolved
 * by class name from the DCA).
 */
final class ContentCallbacks
{
    public function __construct(
        private readonly Connection $db,
        private readonly SettingsRepository $settings,
    ) {
    }

    /**
     * Re-labels the core "module" column as "Configuration" when a website
     * check element is edited: a select of the configuration records with an
     * "automatic (by page language)" blank option. Other element types keep
     * the untouched core definition.
     */
    public function configField(DataContainer $dc): void
    {
        if ('edit' !== Input::get('act') || !$dc->id) {
            return;
        }

        $type = $this->db->fetchOne('SELECT type FROM tl_content WHERE id = ?', [(int) $dc->id]);

        if ('neo_websitecheck' !== $type) {
            return;
        }

        System::loadLanguageFile('tl_neo_websitecheck_config');
        $lang = $GLOBALS['TL_LANG']['tl_neo_websitecheck_config'] ?? [];
        $options = [];

        foreach ($this->settings->configs() as $config) {
            $options[$config['id']] = sprintf('%s [%s]', $config['title'], strtoupper($config['language']));
        }

        $GLOBALS['TL_DCA']['tl_content']['fields']['module'] = [
            'label' => $lang['element_config'] ?? ['Configuration', ''],
            'inputType' => 'select',
            'options' => $options,
            'eval' => ['includeBlankOption' => true, 'blankOptionLabel' => $lang['automatic'] ?? 'Automatic', 'tl_class' => 'w50'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ];
        $GLOBALS['TL_LANG']['tl_content']['config_legend'] = $lang['title_legend'] ?? 'Configuration';
    }
}
