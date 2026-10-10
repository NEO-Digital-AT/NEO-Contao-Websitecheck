<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Check;

/**
 * The bundled demo measurement (a fictitious hotel website with placeholder
 * screenshots) used by the back-end previews and by neo:websitecheck:demo, so
 * templates, colours and the mail flow can be tested without API keys.
 */
final class DemoData
{
    public function dir(): string
    {
        return \dirname(__DIR__, 2).'/resources/demo';
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        return json_decode((string) file_get_contents($this->dir().'/data.json'), true) ?: [];
    }

    /** @return list<array<string, mixed>> */
    public function crops(): array
    {
        return [['file' => 'finding-1.jpg', 'width' => 824, 'height' => 300, 'auditId' => 'color-contrast', 'title' => '', 'text' => 'Jetzt buchen', 'explanation' => '', 'ratio' => 2.3]];
    }

    /** @return list<string> */
    public function files(): array
    {
        return ['home-desktop.jpg', 'home-mobile.jpg', 'finding-1.jpg'];
    }

    public function firstName(string $locale): string
    {
        return 'de' === substr($locale, 0, 2) ? 'Maria' : 'Jane';
    }

    public function lastName(string $locale): string
    {
        return 'de' === substr($locale, 0, 2) ? 'Muster' : 'Doe';
    }
}
