<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Report;

use Contao\CoreBundle\Framework\ContaoFramework;
use Neo\WebsitecheckBundle\Check\CheckRepository;
use Neo\WebsitecheckBundle\Settings;
use Twig\Environment;

/**
 * Renders the HTML of the PDF report and the e-mails from the Contao Twig
 * templates (overridable in the app's templates/neo_websitecheck/ folder).
 * Images are embedded as data URIs so the same HTML works in Dompdf,
 * Gotenberg and e-mail clients.
 */
final class ReportRenderer
{
    public function __construct(
        private readonly Environment $twig,
        private readonly ContaoFramework $framework,
        private readonly CheckRepository $repository,
        private readonly Settings $settings,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public function renderReport(array $row): string
    {
        return $this->render('@Contao/neo_websitecheck/report.html.twig', $row);
    }

    /**
     * @param array<string, mixed> $row
     */
    public function renderMail(array $row, string $template): string
    {
        return $this->render('@Contao/neo_websitecheck/'.$template.'.html.twig', $row);
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
        $brand = $this->settings->brand();
        $brand['name'] = $brand['name'] ?: $this->siteTitle();
        $brand['logo_data'] = $this->dataUri($this->settings->projectDir().'/'.ltrim($brand['logo'], '/'));
        $brand['portrait_data'] = $this->dataUri($this->settings->projectDir().'/'.ltrim($brand['portrait'], '/'));

        $images = [];

        foreach (['desktop', 'mobile', 'mobileFull'] as $key) {
            $file = $result['screenshots'][$key] ?? null;
            $images[$key] = null !== $file ? $this->dataUri($this->repository->dir((int) $row['id']).'/'.$file) : null;
        }

        $crops = [];

        foreach ((array) ($result['crops'] ?? []) as $crop) {
            $data = $this->dataUri($this->repository->dir((int) $row['id']).'/'.$crop['file']);

            if (null !== $data) {
                $crops[] = $crop + ['data' => $data];
            }
        }

        $locale = (string) ($row['locale'] ?: 'en');
        $date = 'de' === substr($locale, 0, 2) ? date('d.m.Y', (int) ($result['checkedAt'] ?? time())) : date('Y-m-d', (int) ($result['checkedAt'] ?? time()));

        return [
            'check' => $row,
            'result' => $result,
            'areas' => array_column($result['areas'] ?? [], null, 'key'),
            'brand' => $brand,
            'images' => $images,
            'crops' => $crops,
            'locale' => $locale,
            'lang' => substr($locale, 0, 2),
            'date' => $date,
            'contact_url' => $brand['contact_url'] ?: $brand['website_url'],
            'first_name' => (string) $row['firstname'],
            'last_name' => (string) $row['lastname'],
            'email' => (string) $row['email'],
            'newsletter' => '1' === (string) $row['newsletter'],
            'retention_days' => $this->settings->retentionDays(),
        ];
    }

    private function render(string $template, array $row): string
    {
        return $this->twig->render($template, $this->context($row));
    }

    private function siteTitle(): string
    {
        try {
            $roots = $this->framework->getAdapter(\Contao\PageModel::class)->findPublishedRootPages();

            if (null === $roots || null === ($root = $roots->current())) {
                return '';
            }

            return (string) ($root->row()['title'] ?? '');
        } catch (\Throwable) {
            return '';
        }
    }

    private function dataUri(string $path): string|null
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

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
