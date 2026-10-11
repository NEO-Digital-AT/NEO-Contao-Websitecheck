<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Report;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\PageModel;
use Neo\WebsitecheckBundle\Check\CheckRepository;
use Neo\WebsitecheckBundle\Design\DesignRenderer;
use Neo\WebsitecheckBundle\Design\Presets;
use Neo\WebsitecheckBundle\Settings;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Renders the HTML of the PDF report and the e-mails from the Contao Twig
 * templates (overridable in the app's templates/neo_websitecheck/ folder).
 * Images are embedded as data URIs so the same HTML works in Dompdf,
 * Gotenberg and e-mail clients. Texts come from wc_text() in the templates
 * (back-end override or translation) with the placeholder values in "vars".
 */
final class ReportRenderer
{
    public function __construct(
        private readonly Environment $twig,
        private readonly ContaoFramework $framework,
        private readonly CheckRepository $repository,
        private readonly Settings $settings,
        private readonly ContentUrlGenerator $urlGenerator,
        private readonly DesignRenderer $designs,
        private readonly TranslatorInterface $translator,
        private readonly Presets $presets,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public function renderReport(array $row): string
    {
        return $this->render('', $row, 'pdf_body', 'pdf_design', 'pdf');
    }

    /**
     * @param array<string, mixed> $row
     */
    public function renderMail(array $row, string $template): string
    {
        return match ($template) {
            'mail_visitor' => $this->render('', $row, 'mail_body', 'mail_design', 'mail'),
            'mail_owner' => $this->render('@Contao/neo_websitecheck/mail_owner.html.twig', $row, 'owner_body', null, null),
            default => $this->render('@Contao/neo_websitecheck/'.$template.'.html.twig', $row, null, null, null),
        };
    }

    /**
     * Renders a design (draft from the designer or stored JSON) for the row —
     * used by the back-end live preview.
     *
     * @param array<string, mixed>             $row
     * @param array<string, mixed>|string|null $design
     */
    public function renderDesign(string $kind, array|string|null $design, array $row, bool $markers = false): string
    {
        return $this->inLocale((string) ($row['locale'] ?: 'en'), function () use ($kind, $design, $row, $markers): string {
            $context = $this->context($row);
            $context += $context['vars'];

            return $this->designs->render($kind, $design, $context, $markers);
        });
    }

    /**
     * Renders a Twig source of the advanced mode (draft from the designer)
     * with the same context as a stored one.
     *
     * @param array<string, mixed> $row
     */
    public function renderTwig(string $source, array $row): string
    {
        return $this->inLocale((string) ($row['locale'] ?: 'en'), function () use ($source, $row): string {
            $context = $this->context($row);
            $context += $context['vars'];

            return $this->twig->createTemplate($source)->render($context);
        });
    }

    /**
     * Runs $render with the translator switched to the check's language, so
     * every fixed label (|trans in the templates) matches the report — not the
     * language of the current request (console, API call, back end).
     *
     * @template T
     *
     * @param callable(): T $render
     *
     * @return T
     */
    private function inLocale(string $locale, callable $render): mixed
    {
        if (!$this->translator instanceof LocaleAwareInterface) {
            return $render();
        }

        $previous = $this->translator->getLocale();
        $this->translator->setLocale($locale);

        try {
            return $render();
        } finally {
            $this->translator->setLocale($previous);
        }
    }

    /** The configuration record of a check row (explicit id, else by language). */
    public function configFor(array $row): array|null
    {
        return $this->settings->db()?->config((int) ($row['config_id'] ?? 0), (string) ($row['locale'] ?: 'en'));
    }

    /**
     * Template context shared by report and e-mails.
     *
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    public function context(array $row): array
    {
        $this->framework->initialize();

        $result = (array) $row['result'];
        $locale = (string) ($row['locale'] ?: 'en');
        $brand = $this->settings->brand();
        $brand['name'] = $brand['name'] ?: $this->siteTitle();
        // Pages of the check's configuration (= its language) before the global ones.
        $configPages = $this->settings->db()?->config((int) ($row['config_id'] ?? 0), $locale) ?? [];
        $brand['contact_url'] = $this->pageUrl((int) ($configPages['contact_page'] ?? 0)) ?: $this->pageUrl((int) $brand['contact_page']) ?: (string) $brand['contact_url'];
        $brand['privacy_url'] = $this->pageUrl((int) ($configPages['privacy_page'] ?? 0)) ?: $this->pageUrl((int) $brand['privacy_page']) ?: (string) $brand['privacy_url'];
        // Logos and the portrait are shown small (header 7–10 mm, portrait 22 mm): embed them
        // downscaled, a camera-size portrait would otherwise add megabytes to every e-mail and PDF.
        $brand['logo_data'] = $this->dataUri($this->projectFile((string) $brand['logo']), 600);
        $brand['logo_dark_data'] = $this->dataUri($this->projectFile((string) $brand['logo_dark']), 600) ?? $brand['logo_data'];
        $brand['portrait_data'] = $this->dataUri($this->projectFile((string) $brand['portrait']), 320);

        $dir = isset($row['_demo_dir']) ? (string) $row['_demo_dir'] : $this->repository->dir((int) $row['id']);
        $images = [];

        foreach (['desktop', 'mobile', 'mobileFull'] as $key) {
            $file = $result['screenshots'][$key] ?? null;
            $images[$key] = null !== $file ? $this->dataUri($dir.'/'.$file) : null;
        }

        $crops = [];

        foreach ((array) ($result['crops'] ?? []) as $crop) {
            $data = $this->dataUri($dir.'/'.$crop['file']);

            if (null !== $data) {
                $crops[] = $crop + ['data' => $data];
            }
        }

        $date = 'de' === substr($locale, 0, 2) ? date('d.m.Y', (int) ($result['checkedAt'] ?? time())) : date('Y-m-d', (int) ($result['checkedAt'] ?? time()));
        // Only a page the customer chose: we do not know other sites' structure, so
        // no guessing (the start page is not a contact page). Empty = no button.
        $contactUrl = (string) $brand['contact_url'];
        $config = $this->configFor($row);

        return [
            'check' => $row,
            'config' => $config,
            'result' => $result,
            'areas' => array_column($result['areas'] ?? [], null, 'key'),
            'brand' => $brand,
            'images' => $images,
            'crops' => $crops,
            'locale' => $locale,
            'lang' => substr($locale, 0, 2),
            'date' => $date,
            'contact_url' => $contactUrl,
            'first_name' => (string) $row['firstname'],
            'last_name' => (string) $row['lastname'],
            'email' => (string) $row['email'],
            'newsletter' => '1' === (string) $row['newsletter'],
            'retention_days' => $this->settings->retentionDays(),
            // Placeholder values for wc_text() ({{firstname}}, {{domain}}, …).
            'vars' => [
                'firstname' => (string) $row['firstname'],
                'lastname' => (string) $row['lastname'],
                'domain' => (string) ($result['domain'] ?? $row['domain']),
                'grade' => (string) ($result['grade'] ?? ''),
                'grade_word' => (string) ($result['gradeWord'] ?? ''),
                'pages' => (string) ($result['pagesChecked'] ?? ''),
                'company' => (string) $brand['name'],
                'owner' => (string) $brand['owner_name'],
                'phone' => (string) $brand['phone'],
                'website' => (string) $brand['website_url'],
                'days' => (string) $this->settings->retentionDays(),
                'date' => $date,
                'newsletter' => '1' === (string) $row['newsletter'] ? '+NL' : '',
                'config_id' => (int) ($config['id'] ?? 0),
            ],
        ];
    }

    /**
     * Resolution order: the expert Twig template of the configuration (when
     * filled), then the designed blocks of the configuration, then the
     * bundled "standard" design in the check's language (a configuration that
     * was never opened in the designer). Report and visitor e-mail therefore
     * always come out of the designer blocks; only the internal operator copy
     * uses a file template. {{firstname}} etc. work in Twig sources as plain
     * placeholders because the vars are exposed at top level.
     */
    private function render(string $template, array $row, string|null $twigColumn, string|null $designColumn, string|null $kind): string
    {
        return $this->inLocale((string) ($row['locale'] ?: 'en'), function () use ($template, $row, $twigColumn, $designColumn, $kind): string {
            $context = $this->context($row);
            $context += $context['vars'];
            $config = (array) ($context['config'] ?? []);

            if (null !== $twigColumn && '' !== trim((string) ($config[$twigColumn] ?? ''))) {
                return $this->twig->createTemplate((string) $config[$twigColumn])->render($context);
            }

            if (null !== $designColumn && null !== $kind) {
                $design = trim((string) ($config[$designColumn] ?? ''));

                return $this->designs->render($kind, '' !== $design ? $design : $this->presets->default($kind, (string) $context['locale']), $context);
            }

            return $this->twig->render($template, $context);
        });
    }

    /** Absolute URL of a back-end picked page, '' when unset or unpublished. */
    private function pageUrl(int $id): string
    {
        if ($id <= 0) {
            return '';
        }

        try {
            $page = $this->framework->getAdapter(PageModel::class)->findPublishedById($id);

            return null === $page ? '' : $this->urlGenerator->generate($page, [], UrlGeneratorInterface::ABSOLUTE_URL);
        } catch (\Throwable) {
            return '';
        }
    }

    private function projectFile(string $path): string
    {
        $path = trim($path);

        return '' === $path ? '' : $this->settings->projectDir().'/'.ltrim($path, '/');
    }

    private function siteTitle(): string
    {
        try {
            $roots = $this->framework->getAdapter(PageModel::class)->findPublishedRootPages();

            if (null === $roots || null === ($root = $roots->current())) {
                return '';
            }

            return (string) ($root->row()['title'] ?? '');
        } catch (\Throwable) {
            return '';
        }
    }

    private function dataUri(string $path, int $maxWidth = 0): string|null
    {
        if ('' === trim($path, '/') || !is_file($path)) {
            return null;
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => null,
        };

        if (null === $mime) {
            return null;
        }

        $binary = (string) file_get_contents($path);

        if ($maxWidth > 0 && \in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true) && \function_exists('imagecreatefromstring')) {
            $img = @imagecreatefromstring($binary);

            if (false !== $img && imagesx($img) > $maxWidth) {
                $scaled = imagescale($img, $maxWidth, -1, IMG_BICUBIC);

                if (false !== $scaled) {
                    ob_start();

                    // PNG keeps transparency (logos), photos become JPEG
                    if ('image/png' === $mime) {
                        imagealphablending($scaled, false);
                        imagesavealpha($scaled, true);
                        imagepng($scaled, null, 9);
                    } else {
                        imagejpeg($scaled, null, 85);
                        $mime = 'image/jpeg';
                    }

                    $binary = (string) ob_get_clean();
                    imagedestroy($scaled);
                }
            }

            if (false !== $img) {
                imagedestroy($img);
            }
        }

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }
}
