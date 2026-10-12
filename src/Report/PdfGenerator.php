<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Report;

use Dompdf\Dompdf;
use Dompdf\Options;
use Neo\WebsitecheckBundle\Check\CheckRepository;
use Neo\WebsitecheckBundle\Settings;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Turns the report HTML into an A4 PDF. Default engine: Dompdf (pure PHP, no
 * extra service). Optional: Gotenberg (Chromium as a container) for a
 * pixel-perfect rendering of the same template. "none" skips the PDF and the
 * e-mail links to the on-page result only.
 */
final class PdfGenerator
{
    /** Dompdf variant key => [weight, style, file in resources/fonts/arimo] */
    private const FONTS = [
        'normal' => ['normal', 'normal', 'Arimo-Regular.ttf'],
        'bold' => ['bold', 'normal', 'Arimo-Bold.ttf'],
        'italic' => ['normal', 'italic', 'Arimo-Italic.ttf'],
        'bold_italic' => ['bold', 'italic', 'Arimo-BoldItalic.ttf'],
    ];

    public function __construct(
        private readonly ReportRenderer $renderer,
        private readonly CheckRepository $repository,
        private readonly Settings $settings,
        private readonly HttpClientInterface $httpClient,
        private readonly string $projectDir = '',
        private readonly string $uploadPath = 'files',
    ) {
    }

    /**
     * Generates (or returns the cached) PDF for a finished check.
     *
     * @param array<string, mixed> $row
     *
     * @return string|null absolute path of the PDF, null when disabled
     */
    public function generate(array $row, bool $force = false): string|null
    {
        if ('none' === $this->settings->pdfEngine()) {
            return null;
        }

        $dir = $this->repository->dir((int) $row['id']);
        $path = $dir.'/'.$this->fileName($row);

        // Reuse only the PDF this check rendered itself (pdf_path set by us), e.g. for a resend.
        if (!$force && basename($path) === (string) ($row['pdf_path'] ?? '') && is_file($path) && filesize($path) > 1000) {
            return $path;
        }

        (new Filesystem())->mkdir($dir);
        file_put_contents($path, $this->render($this->renderer->renderReport($row)));
        $this->repository->update((int) $row['id'], ['pdf_path' => basename($path)]);

        return $path;
    }

    /** Turns report HTML into PDF bytes with the configured engine (Dompdf unless Gotenberg is set). */
    public function render(string $html): string
    {
        return 'gotenberg' === $this->settings->pdfEngine() && '' !== $this->settings->gotenbergUrl()
            ? $this->viaGotenberg($html)
            : $this->viaDompdf($html);
    }

    /**
     * @param array<string, mixed> $row
     */
    public function fileName(array $row): string
    {
        $pattern = $this->settings->db()?->configValue((int) ($row['config_id'] ?? 0), (string) ($row['locale'] ?: 'en'), 'pdf_filename') ?: 'website-check-{{domain}}-{{date}}.pdf';
        $result = (array) ($row['result'] ?? []);
        $vars = [
            'domain' => (string) ($result['domain'] ?? $row['domain'] ?? 'website'),
            'date' => date('Y-m-d', (int) (($row['finished'] ?? 0) ?: time())),
            'grade' => (string) ($result['grade'] ?? ''),
            'firstname' => (string) ($row['firstname'] ?? ''),
            'lastname' => (string) ($row['lastname'] ?? ''),
            'company' => (string) ($this->settings->brand()['name'] ?? ''),
        ];
        $name = (string) preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/', static fn (array $m): string => $vars[$m[1]] ?? '', $pattern);
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', str_replace(['ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü'], ['ae', 'oe', 'ue', 'ss', 'Ae', 'Oe', 'Ue'], $name)) ?? 'website-check.pdf';
        $name = trim($name, '-.') ?: 'website-check';

        return str_ends_with(strtolower($name), '.pdf') ? $name : $name.'.pdf';
    }

    private function viaDompdf(string $html): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('defaultPaperSize', 'a4');
        $options->set('dpi', 96);
        $fontDir = $this->settings->storageDir().'/fonts';
        $bundledFonts = \dirname(__DIR__, 2).'/resources/fonts';
        $options->set('fontDir', $fontDir);
        $options->set('fontCache', $fontDir);
        $options->set('isFontSubsettingEnabled', true);
        $chroot = [$this->settings->storageDir(), (string) realpath($bundledFonts)];

        // Templates may use fonts and images from the Contao files directory with a
        // relative path, e.g. url('files/fonts/Brand.ttf') or <img src="files/logo.png">.
        $filesDir = '' !== $this->projectDir ? realpath($this->projectDir.'/'.$this->uploadPath) : false;

        if (false !== $filesDir) {
            $chroot[] = $filesDir;
        }

        $options->set('chroot', $chroot);

        $dompdf = new Dompdf($options);

        if (false !== $filesDir) {
            $dompdf->setProtocol('file://');
            $dompdf->setBasePath(rtrim((string) realpath($this->projectDir), '/').'/');
        }

        $this->registerFonts($dompdf, $fontDir, $bundledFonts);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return (string) $dompdf->output();
    }

    /**
     * Makes the bundled fonts (Arimo, SIL OFL) known to Dompdf. Dompdf converts a
     * font once into its font directory (metrics + copy); later runs find it there.
     */
    private function registerFonts(Dompdf $dompdf, string $fontDir, string $bundledFonts): void
    {
        if (!is_dir($fontDir) && !@mkdir($fontDir, 0775, true) && !is_dir($fontDir)) {
            return;
        }

        $metrics = $dompdf->getFontMetrics();
        $known = $metrics->getFontFamilies()['arimo'] ?? [];

        foreach (self::FONTS as $variant => [$weight, $style, $file]) {
            if (isset($known[$variant]) && is_file($known[$variant].'.ufm') && is_file($known[$variant].'.ttf')) {
                continue;
            }
            $metrics->registerFont(['family' => 'Arimo', 'weight' => $weight, 'style' => $style], (string) realpath($bundledFonts.'/arimo/'.$file));
        }
    }

    private function viaGotenberg(string $html): string
    {
        $form = new FormDataPart([
            'files' => new DataPart($html, 'index.html', 'text/html'),
            'paperWidth' => '8.27',
            'paperHeight' => '11.7',
            'marginTop' => '0',
            'marginBottom' => '0',
            'marginLeft' => '0',
            'marginRight' => '0',
            'printBackground' => 'true',
            'preferCssPageSize' => 'true',
        ]);

        $response = $this->httpClient->request('POST', $this->settings->gotenbergUrl().'/forms/chromium/convert/html', [
            'headers' => $form->getPreparedHeaders()->toArray(),
            'body' => $form->bodyToIterable(),
            'timeout' => 60,
        ]);

        return $response->getContent();
    }
}
