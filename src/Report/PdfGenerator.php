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
    public function __construct(
        private readonly ReportRenderer $renderer,
        private readonly CheckRepository $repository,
        private readonly Settings $settings,
        private readonly HttpClientInterface $httpClient,
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

        if (!$force && is_file($path) && filesize($path) > 1000) {
            return $path;
        }

        (new Filesystem())->mkdir($dir);
        $html = $this->renderer->renderReport($row);

        $pdf = 'gotenberg' === $this->settings->pdfEngine() && '' !== $this->settings->gotenbergUrl()
            ? $this->viaGotenberg($html)
            : $this->viaDompdf($html);

        file_put_contents($path, $pdf);
        $this->repository->update((int) $row['id'], ['pdf_path' => basename($path)]);

        return $path;
    }

    /**
     * @param array<string, mixed> $row
     */
    public function fileName(array $row): string
    {
        $domain = preg_replace('/[^a-z0-9.-]/', '-', strtolower((string) $row['domain'])) ?: 'website';

        return sprintf('website-check-%s-%s.pdf', $domain, date('Y-m-d', (int) ($row['finished'] ?: time())));
    }

    private function viaDompdf(string $html): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('defaultPaperSize', 'a4');
        $options->set('dpi', 96);
        $options->set('chroot', $this->settings->storageDir());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return (string) $dompdf->output();
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
