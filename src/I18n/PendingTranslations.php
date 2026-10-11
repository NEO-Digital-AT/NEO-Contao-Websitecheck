<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\I18n;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A page in a language without translation file asks for its texts; they are
 * translated after the response has been sent (kernel.terminate), so the
 * visitor never waits. From the next request on the language is complete.
 */
final class PendingTranslations
{
    /** @var array<string, true> */
    private array $languages = [];

    public function __construct(
        private readonly AiCatalogue $catalogue,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function request(string $locale): void
    {
        if ($this->translator instanceof TranslatorBagInterface && $this->catalogue->isMissing($this->translator, $locale)) {
            $this->languages[$locale] = true;
        }
    }

    #[AsEventListener(KernelEvents::TERMINATE)]
    public function onTerminate(TerminateEvent $event): void
    {
        if ([] === $this->languages || !$this->translator instanceof TranslatorBagInterface) {
            return;
        }

        foreach (array_keys($this->languages) as $locale) {
            $this->catalogue->generate($this->translator, $locale);
        }

        $this->languages = [];
    }
}
