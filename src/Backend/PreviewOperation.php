<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Backend;

use Contao\CoreBundle\Exception\ResponseException;
use Contao\DataContainer;
use Neo\WebsitecheckBundle\Check\CheckRepository;
use Neo\WebsitecheckBundle\Check\DemoData;
use Neo\WebsitecheckBundle\Check\Grader;
use Neo\WebsitecheckBundle\Report\PdfGenerator;
use Neo\WebsitecheckBundle\Report\ReportRenderer;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Back-end "Preview PDF" / "Preview e-mail" (key=preview_pdf|preview_mail on
 * the settings module): renders the templates with the last finished check or,
 * when there is none yet, with the bundled demo data — so logo, colours and
 * texts can be checked right after saving.
 */
final class PreviewOperation
{
    public function __construct(
        private readonly CheckRepository $repository,
        private readonly ReportRenderer $renderer,
        private readonly PdfGenerator $pdf,
        private readonly Grader $grader,
        private readonly DemoData $demo,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function pdf(DataContainer $dc): string
    {
        $row = $this->sampleRow();
        $html = $this->renderer->renderReport($row);
        $bytes = $this->pdf->render($html);

        throw new ResponseException(new Response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->pdf->fileName($row).'"',
            'Cache-Control' => 'no-store',
        ]));
    }

    public function mail(DataContainer $dc): string
    {
        $row = $this->sampleRow();

        throw new ResponseException(new Response($this->renderer->renderMail($row, 'mail_visitor'), 200, ['Content-Type' => 'text/html; charset=utf-8', 'Cache-Control' => 'no-store']));
    }

    /**
     * The newest finished check with lead data, else a demo check graded in the
     * back-end language with placeholder screenshots from the bundle.
     *
     * @return array<string, mixed>
     */
    /**
     * @param array<string, mixed>|null $overrides row fields to force (e.g. config_id, locale)
     */
    public function sampleRow(array|null $overrides = null): array
    {
        $row = $this->baseRow();

        return null === $overrides ? $row : array_replace($row, $overrides);
    }

    private function baseRow(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $locale = $request?->getLocale() ?: 'en';
        $forced = strtolower(trim((string) $request?->query->get('lang', '')));

        if (preg_match('/^[a-z]{2,3}(?:[-_][a-z0-9]{2,8}){0,3}$/', $forced)) {
            $locale = $forced;
        }

        $configId = (int) $request?->query->get('config', 0);
        $row = $this->repository->findLatestDone();

        if (null !== $row && '' !== (string) $row['email']) {
            if ($configId > 0) {
                $row['config_id'] = $configId;
            }

            // Re-grade in the requested language so a language preview shows that language.
            if (substr((string) $row['locale'], 0, 2) !== $locale) {
                $row['locale'] = $locale;
                $row['result'] = $this->grader->grade((array) $row['data'], $locale) + ['crops' => $row['result']['crops'] ?? [], 'screenshots' => $row['result']['screenshots'] ?? []];
            }

            return $row;
        }

        $demoDir = $this->demo->dir();
        $data = $this->demo->data();
        $result = $this->grader->grade($data, $locale);
        $result['crops'] = $this->demo->crops();

        return [
            'id' => 0,
            'domain' => $result['domain'],
            'url' => $result['url'],
            'locale' => $locale,
            'status' => CheckRepository::STATUS_DONE,
            'finished' => time(),
            'firstname' => $this->demo->firstName($locale),
            'lastname' => $this->demo->lastName($locale),
            'email' => 'maria@example.com',
            'newsletter' => '1',
            'pdf_path' => 'demo.pdf',
            'config_id' => $configId,
            'result' => $result,
            'data' => $data,
            '_demo_dir' => $demoDir,
        ];
    }
}
