<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\I18n;

use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Fills the bundle's own domain for languages without a translation file from
 * the AI catalogue (AiCatalogue). Every other domain, and every language that
 * has its own file, goes straight to the decorated translator — so a project
 * translation file always wins and the rest of the site is untouched.
 */
final class TranslatorDecorator implements TranslatorInterface, TranslatorBagInterface, LocaleAwareInterface
{
    public function __construct(
        private readonly TranslatorInterface&TranslatorBagInterface&LocaleAwareInterface $inner,
        private readonly AiCatalogue $catalogue,
    ) {
    }

    public function trans(string $id, array $parameters = [], string|null $domain = null, string|null $locale = null): string
    {
        if (AiCatalogue::DOMAIN === $domain) {
            $locale ??= $this->inner->getLocale();

            if (!$this->inner->getCatalogue($locale)->defines($id, $domain)) {
                $text = $this->catalogue->messages($locale)[$id] ?? null;

                if (null !== $text) {
                    return strtr($text, array_map('strval', $parameters));
                }
            }
        }

        return $this->inner->trans($id, $parameters, $domain, $locale);
    }

    public function getCatalogue(string|null $locale = null): MessageCatalogueInterface
    {
        return $this->inner->getCatalogue($locale);
    }

    public function getCatalogues(): array
    {
        return $this->inner->getCatalogues();
    }

    public function setLocale(string $locale): void
    {
        $this->inner->setLocale($locale);
    }

    public function getLocale(): string
    {
        return $this->inner->getLocale();
    }

    /** Pass any other method (e.g. of the framework translator) through unchanged. */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->inner->{$method}(...$arguments);
    }
}
