<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Newsletter;

use Neo\WebsitecheckBundle\Settings;

/**
 * Signs a visitor up for the newsletter with the system in use: the NEO Brevo
 * module when installed, otherwise Contao's own newsletter module
 * (Settings::newsletterProvider()). Both use double opt-in.
 */
final class Subscriber
{
    public function __construct(
        private readonly Settings $settings,
        private readonly BrevoSubscriber $brevo,
        private readonly ContaoSubscriber $contao,
    ) {
    }

    public function subscribe(string $email, string $firstName, string $lastName, int $configId = 0, string $locale = ''): bool
    {
        if (!$this->settings->newsletterEnabled($configId, $locale)) {
            return false;
        }

        return match ($this->settings->newsletterProvider()) {
            'brevo' => $this->brevo->subscribe($email, $firstName, $lastName, $configId, $locale),
            'contao' => $this->contao->subscribe($email, $configId, $locale),
            default => false,
        };
    }
}
