<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Report;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\PageModel;
use Neo\WebsitecheckBundle\Check\CheckRepository;
use Neo\WebsitecheckBundle\Settings;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public function renderReport(array $row): string
    {
        return $this->render('@Contao/neo_websitecheck/report.html.twig', $row, 'pdf_body');
    }

    /**
     * @param array<string, mixed> $row
     */
    public function renderMail(array $row, string $template): string
    {
        return $this->render('@Contao/neo_websitecheck/'.$template.'.html.twig', $row, 'mail_visitor' === $template ? 'mail_body' : ('mail_owner' === $template ? 'owner_body' : null));
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
        $brand['contact_url'] = $this->pageUrl((int) $brand['contact_page']) ?: (string) $brand['contact_url'];
        $brand['privacy_url'] = $this->pageUrl((int) $brand['privacy_page']) ?: (string) $brand['privacy_url'];
        $brand['logo_data'] = $this->dataUri($this->projectFile((string) $brand['logo']));
        $brand['logo_dark_data'] = $this->dataUri($this->projectFile((string) $brand['logo_dark'])) ?? $brand['logo_data'];
        $brand['portrait_data'] = $this->dataUri($this->projectFile((string) $brand['portrait']));

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
        $contactUrl = $brand['contact_url'] ?: (string) $brand['website_url'];

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
            ],
        ];
    }

    /**
     * Renders the file template — or, when the operator saved a complete
     * template for the language in the back end, that one (Twig source with
     * the same variables; {{firstname}} etc. work there as plain placeholders
     * because the vars are also exposed at top level).
     */
    private function render(string $template, array $row, string|null $customColumn): string
    {
        $context = $this->context($row);
        $context += $context['vars'];

        if (null !== $customColumn) {
            $custom = $this->settings->db()?->text(substr((string) ($row['locale'] ?: 'en'), 0, 2), $customColumn) ?? '';

            if ('' !== $custom) {
                return $this->twig->createTemplate($custom)->render($context);
            }
        }

        return $this->twig->render($template, $context);
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
