<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\EventListener;

use Contao\CoreBundle\Event\DataContainerRecordLabelEvent;
use Contao\System;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Breadcrumb labels for the settings record ("Edit settings") and the language
 * records ("Language DE") and checks ("example.com · Jane Doe") instead of
 * Contao's generic "Edit element ID 1".
 */
#[AsEventListener]
final class RecordLabelListener
{
    public function __invoke(DataContainerRecordLabelEvent $event): void
    {
        if (null !== $event->getLabel()) {
            return;
        }

        $id = $event->getIdentifier();

        if (str_starts_with($id, 'contao.db.tl_neo_websitecheck_settings.')) {
            System::loadLanguageFile('tl_neo_websitecheck_settings');
            $event->setLabel((string) ($GLOBALS['TL_LANG']['tl_neo_websitecheck_settings']['edit'][1] ?? 'Settings'));
        } elseif (str_starts_with($id, 'contao.db.tl_neo_websitecheck.')) {
            $row = $event->getData();
            $event->setLabel(trim(sprintf('%s · %s %s', (string) ($row['domain'] ?? ''), (string) ($row['firstname'] ?? ''), (string) ($row['lastname'] ?? '')), ' ·'));
        } elseif (str_starts_with($id, 'contao.db.tl_neo_websitecheck_text.')) {
            System::loadLanguageFile('tl_neo_websitecheck_text');
            $event->setLabel(sprintf((string) ($GLOBALS['TL_LANG']['tl_neo_websitecheck_text']['recordLabel'] ?? 'Language %s'), strtoupper((string) ($event->getData()['language'] ?? ''))));
        }
    }
}
