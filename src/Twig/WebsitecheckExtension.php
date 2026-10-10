<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Twig;

use Neo\WebsitecheckBundle\Text\TextProvider;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * wc_text('mail.greeting', vars, locale): the operator's text from the back
 * end, or the translation — with placeholders replaced.
 */
final class WebsitecheckExtension extends AbstractExtension
{
    public function __construct(private readonly TextProvider $texts)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('wc_text', fn (string $key, array $vars = [], string $locale = 'en'): string => $this->texts->get($key, $locale, $vars)),
        ];
    }
}
