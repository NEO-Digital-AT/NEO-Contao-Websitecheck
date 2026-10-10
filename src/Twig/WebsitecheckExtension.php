<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Twig;

use Neo\WebsitecheckBundle\Design\BlockRegistry;
use Neo\WebsitecheckBundle\Design\DesignRenderer;
use Neo\WebsitecheckBundle\Text\TextProvider;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * wc_text('mail.subject', vars, locale): the operator's text from the back
 * end, or the translation, with placeholders replaced.
 *
 * wc_theme(kind, {…}) and wc_block(kind, type, t, {…}): used by Twig sources
 * of the advanced mode (DesignRenderer::toTwig()). They turn the maps written
 * in the template into what the block partials expect: validated values,
 * {{placeholders}} filled, brand colours and units resolved.
 */
final class WebsitecheckExtension extends AbstractExtension
{
    public function __construct(
        private readonly TextProvider $texts,
        private readonly BlockRegistry $registry,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('wc_text', fn (string $key, array $vars = [], string $locale = 'en'): string => $this->texts->get($key, $locale, $vars)),
            new TwigFunction('wc_theme', fn (array $context, string $kind, array $theme = []): array => DesignRenderer::twigTheme($this->kind($kind), $theme, $context, $this->resolver($context)), ['needs_context' => true]),
            new TwigFunction('wc_block', fn (array $context, string $kind, string $type, array $t, array $props = []): array => DesignRenderer::twigBlock($this->kind($kind), $type, $props, $t, $context, $this->resolver($context)), ['needs_context' => true]),
        ];
    }

    private function kind(string $kind): string
    {
        return BlockRegistry::KIND_MAIL === $kind ? BlockRegistry::KIND_MAIL : BlockRegistry::KIND_PDF;
    }

    /** @param array<string, mixed> $context */
    private function resolver(array $context): callable
    {
        return $this->registry->resolver((string) ($context['locale'] ?? 'en'));
    }
}
