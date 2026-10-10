<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Backend;

use Contao\CoreBundle\Exception\ResponseException;
use Contao\DataContainer;
use Contao\Dbafs;
use Neo\WebsitecheckBundle\Design\BlockRegistry;
use Neo\WebsitecheckBundle\Design\Design;
use Neo\WebsitecheckBundle\Design\DesignRenderer;
use Neo\WebsitecheckBundle\Design\Presets;
use Neo\WebsitecheckBundle\Report\PdfGenerator;
use Neo\WebsitecheckBundle\Report\ReportRenderer;
use Neo\WebsitecheckBundle\Settings;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Endpoints of the visual designer (BE_MOD "key=" operations, back-end
 * session required): live preview of a draft, PDF of a draft, presets per
 * language, image upload into files/neo-websitecheck, Twig export.
 * Drafts are posted as JSON {kind, design, lang, config} and never stored
 * here — saving is the normal form submit.
 */
final class DesignerOperation
{
    private const MAX_UPLOAD = 6 * 1024 * 1024;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ReportRenderer $renderer,
        private readonly DesignRenderer $designs,
        private readonly Presets $presets,
        private readonly PreviewOperation $previews,
        private readonly PdfGenerator $pdf,
        private readonly Settings $settings,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function preview(DataContainer $dc): string
    {
        [$kind, $design, $row] = $this->draft();
        $html = $this->renderer->renderDesign($kind, $design, $row);

        // Preview helpers: block selection (postMessage), page breaks and fixed header/footer as plain blocks.
        $inject = '<style>'
            .'.pdf-head,.pdf-foot{position:static!important;left:auto;right:auto;top:auto;bottom:auto;margin:0 0 6mm}.pdf-foot{margin:8mm 0 0}'
            .'body{padding:10mm 12mm}.nwc-b{position:relative;cursor:pointer;outline:2px solid transparent;outline-offset:3px;border-radius:3px;transition:outline-color .12s}'
            .'.nwc-b:hover{outline-color:#9aa8ff}.nwc-b.is-selected{outline-color:#4a5cff}'
            .'.nwc-b--page_break>div{border-top:2px dashed #b9b4cc;height:0;margin:4mm 0;position:relative}.nwc-b--page_break>div:after{content:attr(data-label);position:absolute;right:0;top:-9px;font:11px/1 sans-serif;color:#8a86a0;background:#fff;padding:0 6px}'
            .'</style>'
            .'<script>(function(){var cur=null;document.addEventListener("click",function(e){var b=e.target.closest("[data-nwc-block]");if(!b)return;e.preventDefault();if(e.target.closest("a"))e.preventDefault();select(b.getAttribute("data-nwc-block"));parent.postMessage({nwd:"select",id:b.getAttribute("data-nwc-block")},"*");});'
            .'function select(id){if(cur)cur.classList.remove("is-selected");cur=document.querySelector(\'[data-nwc-block="\'+id+\'"]\');if(cur){cur.classList.add("is-selected");}}'
            .'window.addEventListener("message",function(e){if(!e.data||e.data.nwd!=="select")return;select(e.data.id);if(cur&&e.data.scroll)cur.scrollIntoView({block:"center",behavior:"smooth"});});'
            .'document.querySelectorAll(".nwc-b--page_break>div").forEach(function(d){d.setAttribute("data-label","'.addslashes($this->translator->trans('designer.block.page_break.label', [], 'neo_websitecheck', $this->locale(''))).'");});'
            .'parent.postMessage({nwd:"ready",height:document.body.scrollHeight},"*");})();</script>';

        $html = str_contains($html, '</body>') ? str_replace('</body>', $inject.'</body>', $html) : $html.$inject;

        throw new ResponseException(new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8', 'Cache-Control' => 'no-store', 'X-Frame-Options' => 'SAMEORIGIN']));
    }

    public function pdf(DataContainer $dc): string
    {
        [$kind, $design, $row] = $this->draft();
        $bytes = $this->pdf->render($this->renderer->renderDesign(BlockRegistry::KIND_PDF, $design, $row));

        throw new ResponseException(new Response($bytes, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$this->pdf->fileName($row).'"', 'Cache-Control' => 'no-store']));
    }

    public function preset(DataContainer $dc): string
    {
        $request = $this->request();
        $kind = $this->kind((string) $request->query->get('kind', ''));
        $locale = $this->locale((string) $request->query->get('lang', ''));
        $design = $this->presets->load($kind, (string) $request->query->get('name', 'standard'), $locale);

        throw new ResponseException(new JsonResponse(null === $design ? ['error' => 'unknown_preset'] : $design, null === $design ? 404 : 200));
    }

    public function twig(DataContainer $dc): string
    {
        [$kind, $design] = $this->draft(false);

        throw new ResponseException(new Response($this->designs->toTwig($kind, $design), 200, ['Content-Type' => 'text/plain; charset=utf-8']));
    }

    /** Stores an uploaded image below files/neo-websitecheck and registers it in the DBAFS. */
    public function upload(DataContainer $dc): string
    {
        $file = $this->request()->files->get('file');

        if (null === $file || !$file->isValid() || $file->getSize() > self::MAX_UPLOAD) {
            throw new ResponseException(new JsonResponse(['error' => 'invalid_file'], 400));
        }

        $ext = strtolower((string) $file->guessExtension() ?: $file->getClientOriginalExtension());
        $mime = (string) $file->getMimeType();

        if (!\in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true) || !str_starts_with($mime, 'image/')) {
            throw new ResponseException(new JsonResponse(['error' => 'not_an_image'], 400));
        }

        $base = preg_replace('/[^a-z0-9_-]+/', '-', strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))) ?: 'image';
        $dir = $this->settings->projectDir().'/files/neo-websitecheck';
        (new Filesystem())->mkdir($dir);
        $name = trim($base, '-').'-'.substr(bin2hex(random_bytes(4)), 0, 6).'.'.('jpeg' === $ext ? 'jpg' : $ext);
        $file->move($dir, $name);
        $path = 'files/neo-websitecheck/'.$name;

        try {
            Dbafs::addResource($path);
        } catch (\Throwable) {
            // not fatal: the file is usable by path
        }

        throw new ResponseException(new JsonResponse(['path' => $path]));
    }

    /**
     * @return array{0: string, 1: array<string, mixed>, 2: array<string, mixed>}
     */
    private function draft(bool $withRow = true): array
    {
        $request = $this->request();
        $payload = json_decode((string) $request->getContent(), true);

        if (!\is_array($payload)) {
            $payload = $request->request->all();
        }

        $kind = $this->kind((string) ($payload['kind'] ?? $request->query->get('kind', '')));
        $design = Design::normalise(\is_array($payload['design'] ?? null) ? $payload['design'] : (string) ($payload['design'] ?? ''), $kind);
        $row = [];

        if ($withRow) {
            $locale = $this->locale((string) ($payload['lang'] ?? ''));
            $request->query->set('lang', $locale);
            $request->query->set('config', (int) ($payload['config'] ?? 0));
            $row = $this->previews->sampleRow();
        }

        return [$kind, $design, $row];
    }

    private function kind(string $kind): string
    {
        return BlockRegistry::KIND_MAIL === $kind ? BlockRegistry::KIND_MAIL : BlockRegistry::KIND_PDF;
    }

    private function locale(string $lang): string
    {
        $lang = strtolower(trim($lang));

        return preg_match('/^[a-z]{2}$/', $lang) ? $lang : ((string) $this->request()->getLocale() ?: 'en');
    }

    private function request(): Request
    {
        return $this->requestStack->getCurrentRequest() ?? Request::createFromGlobals();
    }

}
