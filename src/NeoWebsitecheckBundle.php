<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * NEO Website Check — a self-contained Contao 5 bundle that turns "check my
 * website" into a lead magnet: a visitor enters a domain, the bundle measures
 * it (Google PageSpeed Insights plus its own HTML, legal and AI-visibility
 * checks, optionally an LLM reading the pages), grades the result with school
 * grades and sends a PDF report by e-mail once the visitor leaves name and
 * e-mail address.
 *
 * The bundle root holds the Contao resources (contao/, config/, public/,
 * translations/), which is why getPath() points one level above src/.
 */
final class NeoWebsitecheckBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
