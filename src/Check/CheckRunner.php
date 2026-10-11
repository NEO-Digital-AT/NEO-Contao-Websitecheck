<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Check;

use Neo\WebsitecheckBundle\Check\Analyzer\AiVisibilityAnalyzer;
use Neo\WebsitecheckBundle\Check\Analyzer\HtmlAnalyzer;
use Neo\WebsitecheckBundle\Check\Analyzer\LegalAnalyzer;
use Neo\WebsitecheckBundle\Settings;
use Neo\WebsitecheckBundle\I18n\AiCatalogue;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The check as a resumable step machine. Each step is short enough to run
 * inside one HTTP request (the status endpoint advances the check while the
 * visitor polls), so the bundle works on shared hosting without a Messenger
 * worker or cron. A console command / the Contao cron can drive it as well.
 *
 * Steps: discover → speed_home → speed_pages → analyze → ai → finish.
 * A failing step is retried once, then recorded under data.errors and skipped,
 * so a PageSpeed outage degrades the report instead of breaking it.
 */
final class CheckRunner
{
    public const STEPS = ['discover', 'speed_home', 'speed_pages', 'analyze', 'ai', 'finish'];

    /** Typical seconds per step; refined with the averages of the last runs. */
    private const EXPECTED = ['discover' => 8, 'speed_home' => 40, 'speed_pages' => 35, 'analyze' => 8, 'ai' => 30, 'finish' => 2];

    private const LOCK_SECONDS = 150;

    public function __construct(
        private readonly CheckRepository $repository,
        private readonly SiteFetcher $fetcher,
        private readonly PageDiscovery $discovery,
        private readonly PageSpeedClient $pageSpeed,
        private readonly ScreenshotStore $screenshots,
        private readonly HtmlAnalyzer $htmlAnalyzer,
        private readonly LegalAnalyzer $legalAnalyzer,
        private readonly AiVisibilityAnalyzer $aiVisibilityAnalyzer,
        private readonly AiReviewer $aiReviewer,
        private readonly Grader $grader,
        private readonly Settings $settings,
        private readonly AiCatalogue $catalogue,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface|null $logger = null,
    ) {
    }

    /**
     * Runs the next pending step if nobody else is working on this check.
     * Returns the refreshed row (or null when the id is unknown).
     *
     * @return array<string, mixed>|null
     */
    public function advance(int $id): array|null
    {
        $row = $this->repository->find($id);

        if (null === $row || !\in_array($row['status'], [CheckRepository::STATUS_QUEUED, CheckRepository::STATUS_RUNNING], true)) {
            return $row;
        }

        if (!$this->repository->lock($id, self::LOCK_SECONDS)) {
            return $row;
        }

        // Finish the step even if the polling request is aborted by the browser.
        ignore_user_abort(true);
        @set_time_limit(self::LOCK_SECONDS + 30);

        $step = $this->nextStep($row);
        $data = $row['data'];
        $started = microtime(true);
        $update = ['status' => CheckRepository::STATUS_RUNNING, 'step' => $step];

        try {
            $data = $this->runStep($step, $id, $data, (string) $row['locale']);
            $update['attempts'] = 0;
        } catch (\Throwable $e) {
            $this->logger?->warning(sprintf('Website check #%d step %s failed: %s', $id, $step, $e->getMessage()));
            $attempts = (int) $row['attempts'] + 1;

            if ($attempts < 2) {
                // Retry this step on the next poll.
                $this->repository->update($id, ['attempts' => $attempts, 'status' => CheckRepository::STATUS_RUNNING, 'step' => $this->previousStep($step), 'locked_until' => 0]);

                return $this->repository->find($id);
            }

            $data['errors'][$step] = mb_substr($e->getMessage(), 0, 500);
            $update['attempts'] = 0;

            // Without a reachable start page every later step would grade nothing (and the visitor
            // would get a report full of "not measured" plus a 5): stop with a clear failure instead.
            if ('finish' === $step || 'discover' === $step) {
                $update['status'] = CheckRepository::STATUS_FAILED;
                $update['error'] = mb_substr($e->getMessage(), 0, 1000);
            }
        }

        $times = $row['step_times'];
        $times[$step] = round(microtime(true) - $started, 1);
        $update['step_times'] = $times;
        $update['data'] = $data;
        $update['locked_until'] = 0;

        if ('finish' === $step && CheckRepository::STATUS_FAILED !== $update['status']) {
            $update['status'] = CheckRepository::STATUS_DONE;
            $update['finished'] = time();
            $update['result'] = $data['_result'];
            $update['grade'] = (int) ($data['_result']['grade'] ?? 0);
            $update['summary'] = $this->plainSummary($data['_result']);
            unset($update['data']['_result']);
        }

        $this->repository->update($id, $update);

        return $this->repository->find($id);
    }

    /** Drives a check to the end (console command). */
    public function runToCompletion(int $id, int $maxSteps = 12): array|null
    {
        $row = null;

        for ($i = 0; $i < $maxSteps; ++$i) {
            $row = $this->advance($id);

            if (null === $row || !\in_array($row['status'], [CheckRepository::STATUS_QUEUED, CheckRepository::STATUS_RUNNING], true)) {
                break;
            }
        }

        return $row;
    }

    /**
     * Progress information for the front end: step states, percentage and the
     * estimated remaining seconds.
     *
     * @param array<string, mixed> $row
     *
     * @return array{steps: list<array{key: string, state: string}>, progress: int, eta: int}
     */
    public function progress(array $row): array
    {
        $expected = $this->expected();
        $done = CheckRepository::STATUS_DONE === $row['status'] || CheckRepository::STATUS_FAILED === $row['status'];
        $current = (string) $row['step'];
        $currentIndex = '' === $current ? -1 : (int) array_search($current, self::STEPS, true);
        $steps = [];
        $remaining = 0.0;
        $total = array_sum($expected);
        $elapsedTotal = 0.0;

        foreach (self::STEPS as $i => $key) {
            if ($done || $i < $currentIndex || ($i === $currentIndex && 0 === (int) $row['locked_until'])) {
                $state = 'done';
                $elapsedTotal += $expected[$key];
            } elseif ($i === $currentIndex || ($i === $currentIndex + 1 && 0 === (int) $row['locked_until'])) {
                $state = 'run';
                $inStep = (int) $row['locked_until'] > 0 ? max(0, time() - (int) $row['step_started']) : 0;
                $remaining += max(0, $expected[$key] - $inStep);
                $elapsedTotal += min($expected[$key], $inStep);
            } else {
                $state = 'todo';
                $remaining += $expected[$key];
            }

            $steps[] = ['key' => $key, 'state' => $state];
        }

        return [
            'steps' => $steps,
            'progress' => $done ? 100 : (int) min(97, round($elapsedTotal / max(1, $total) * 100)),
            'eta' => $done ? 0 : (int) ceil($remaining),
        ];
    }

    /** @return array<string, float> */
    private function expected(): array
    {
        $expected = self::EXPECTED;

        if (!$this->aiReviewer->isEnabled()) {
            $expected['ai'] = 0;
        }

        foreach ($this->repository->averageStepTimes() as $step => $avg) {
            if (isset($expected[$step]) && $expected[$step] > 0) {
                $expected[$step] = round(($expected[$step] + 2 * $avg) / 3, 1);
            }
        }

        return $expected;
    }

    private function nextStep(array $row): string
    {
        $current = (string) $row['step'];

        if ('' === $current) {
            return self::STEPS[0];
        }

        $index = array_search($current, self::STEPS, true);

        return self::STEPS[min(\count(self::STEPS) - 1, (int) $index + 1)];
    }

    private function previousStep(string $step): string
    {
        $index = (int) array_search($step, self::STEPS, true);

        return $index > 0 ? self::STEPS[$index - 1] : '';
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function runStep(string $step, int $id, array $data, string $locale): array
    {
        return match ($step) {
            'discover' => $this->discover($id, $data),
            'speed_home' => $this->speedHome($id, $data, $locale),
            'speed_pages' => $this->speedPages($data, $locale),
            'analyze' => $this->analyze($data),
            'ai' => $this->ai($id, $data, $locale),
            'finish' => $this->finish($data, $locale),
            default => throw new \LogicException('Unknown step '.$step),
        };
    }

    // ---------------------------------------------------------------- steps

    private function discover(int $id, array $data): array
    {
        $row = $this->repository->find($id);
        $url = (string) $row['url'];
        $home = $this->fetcher->fetch($url);

        if ($home['status'] >= 400 || '' !== $home['error'] || '' === $home['body']) {
            // No https? Try plain http once.
            $plain = preg_replace('~^https://~', 'http://', $url) ?? $url;
            $retry = $this->fetcher->fetch($plain);

            if ($retry['status'] < 400 && '' === $retry['error'] && '' !== $retry['body']) {
                $home = $retry;
            } elseif ('' !== $home['error']) {
                throw new \RuntimeException('Start page not reachable: '.$home['error']);
            } else {
                throw new \RuntimeException('Start page answered with HTTP '.$home['status']);
            }
        }

        $finalUrl = $home['url'];
        $host = (string) parse_url($finalUrl, PHP_URL_HOST);
        $origin = (parse_url($finalUrl, PHP_URL_SCHEME) ?: 'https').'://'.$host;

        // Does plain http redirect to https?
        $httpProbe = $this->fetcher->fetch('http://'.$host.'/');
        $httpRedirect = str_starts_with($httpProbe['url'], 'https://') || (bool) array_filter($httpProbe['redirects'], static fn (string $l): bool => str_starts_with($l, 'https://'));

        $aux = $this->fetcher->fetchAll([$origin.'/robots.txt', $origin.'/llms.txt', $origin.'/llms-full.txt', $origin.'/sitemap.xml']);
        $robots = $aux[$origin.'/robots.txt'];

        // Sitemap: from robots.txt or the default location, including index sitemaps.
        $sitemapUrls = [];
        $sitemapFound = false;
        $sitemapLocations = [];

        if ($robots['status'] < 400 && preg_match_all('/^\s*sitemap\s*:\s*(\S+)/im', $robots['body'], $m)) {
            $sitemapLocations = \array_slice($m[1], 0, 3);
        }

        $candidates = [];

        foreach ($sitemapLocations as $loc) {
            $candidates[$loc] = null;
        }

        $candidates[$origin.'/sitemap.xml'] = $aux[$origin.'/sitemap.xml'];
        $toFetch = array_keys(array_filter($candidates, static fn ($v): bool => null === $v));
        $fetched = [] === $toFetch ? [] : $this->fetcher->fetchAll($toFetch);

        foreach ($candidates as $loc => $response) {
            $response ??= $fetched[$loc] ?? null;

            if (null === $response || $response['status'] >= 400 || '' === $response['body']) {
                continue;
            }

            [$urls, $children] = $this->parseSitemap($response['body']);

            if ([] !== $urls || [] !== $children) {
                $sitemapFound = true;
            }

            $sitemapUrls = array_merge($sitemapUrls, $urls);

            if ([] !== $children && \count($sitemapUrls) < 50) {
                foreach ($this->fetcher->fetchAll(\array_slice($children, 0, 2)) as $child) {
                    if ($child['status'] < 400) {
                        $sitemapUrls = array_merge($sitemapUrls, $this->parseSitemap($child['body'])[0]);
                    }
                }
            }

            if ($sitemapFound) {
                break;
            }
        }

        $sitemapUrls = \array_slice(array_values(array_unique($sitemapUrls)), 0, 500);

        $homeHtml = $this->htmlAnalyzer->analyze($finalUrl, $home['body']);
        $homeHtml['status'] = $home['status'];
        $homeHtml['time'] = $home['time'];

        $pages = $this->discovery->select($finalUrl, array_merge($homeHtml['links']['nav'], $homeHtml['links']['internal']), $sitemapUrls, $this->settings->maxPages());

        $trim = static fn (array $r, int $max): array => ['status' => $r['status'], 'body' => mb_substr($r['body'], 0, $max), 'headers' => ['content-type' => $r['headers']['content-type'] ?? []]];

        $data['home'] = [
            'domain' => preg_replace('/^www\./', '', $host),
            'url' => $finalUrl,
            'requested' => $url,
            'status' => $home['status'],
            'https' => str_starts_with($finalUrl, 'https://'),
            'httpRedirect' => $httpRedirect,
            'redirects' => $home['redirects'],
            'www' => str_starts_with($host, 'www.'),
            'server' => $home['headers']['server'][0] ?? '',
        ];
        $data['pages'] = $pages;
        $data['html'] = [$finalUrl => $homeHtml];
        $data['robots'] = $trim($robots, 20000);
        $data['llms'] = $trim($aux[$origin.'/llms.txt'], 3000);
        $data['llmsFull'] = $trim($aux[$origin.'/llms-full.txt'], 500);
        $data['sitemap'] = ['found' => $sitemapFound, 'urls' => \count($sitemapUrls)];

        return $data;
    }

    private function speedHome(int $id, array $data, string $locale): array
    {
        $url = (string) $data['home']['url'];
        $results = $this->pageSpeed->runMany([
            'home_mobile' => ['url' => $url, 'strategy' => 'mobile'],
            'home_desktop' => ['url' => $url, 'strategy' => 'desktop'],
        ], $locale);

        $mobile = $results['home_mobile'];
        $desktop = $results['home_desktop'];

        if (isset($mobile['error'])) {
            // Without the mobile run the whole speed/a11y/mobile picture is missing — worth a retry.
            throw new \RuntimeException('PageSpeed (mobile): '.$mobile['error']);
        }

        $data['screenshots'] = [];
        $data['pins'] = [];
        $data['crops'] = [];

        $full = $mobile['screenshots']['full'];
        $viewport = $mobile['screenshots']['viewport'] ?? ['width' => 412, 'height' => 823];
        // first screen in full resolution from the full-page capture; Google's thumbnail only as a fallback
        $final = null !== $full && '' !== $full['data'] ? $this->screenshots->saveFirstScreen($id, 'home-mobile', $full['data'], (int) ($full['width'] ?: $viewport['width']), (int) $viewport['height'], 900) : null;
        $final ??= $this->screenshots->saveDataUrl($id, 'home-mobile', $mobile['screenshots']['final'], 0, 900);

        if (null !== $final) {
            $data['screenshots']['mobile'] = $final;
        }

        $fullSaved = null;

        if (null !== $full && '' !== $full['data']) {
            // the whole page (findings often sit far down); only the excerpts go into PDF and e-mail
            $fullSaved = $this->screenshots->saveDataUrl($id, 'home-mobile-full', $full['data'], (int) $full['width'], 900, 20000);

            if (null !== $fullSaved) {
                $data['screenshots']['mobileFull'] = $fullSaved;
            }
        }

        if (!isset($desktop['error'])) {
            $deskFull = $desktop['screenshots']['full'] ?? null;
            $deskViewport = $desktop['screenshots']['viewport'] ?? ['width' => 1350, 'height' => 940];
            $deskFinal = null !== $deskFull && '' !== $deskFull['data'] ? $this->screenshots->saveFirstScreen($id, 'home-desktop', $deskFull['data'], (int) ($deskFull['width'] ?: $deskViewport['width']), (int) $deskViewport['height'], 1400) : null;
            $deskFinal ??= $this->screenshots->saveDataUrl($id, 'home-desktop', $desktop['screenshots']['final'], 0, 1400);

            if (null !== $deskFinal) {
                $data['screenshots']['desktop'] = $deskFinal;
            }
        }

        // Marked excerpts for the accessibility page (contrast first) + pins for the live preview.
        if (null !== $fullSaved) {
            $n = 0;
            $viewportCss = (int) $viewport['height'];
            // CSS px covered by the stored capture: excerpts below it cannot be cut
            $coveredCss = (int) floor($fullSaved['height'] / max(0.01, $fullSaved['scale']));

            $tops = [];

            foreach ($mobile['a11y'] as $auditId => $audit) {
                $perAudit = 0;

                foreach ($audit['items'] as $item) {
                    $rect = $item['rect'] ?? null;

                    if (null === $rect || $rect['width'] <= 0 || $rect['height'] <= 0 || $rect['top'] + $rect['height'] > $coveredCss || PageSpeedClient::isIconGlyph($item)) {
                        continue;
                    }

                    // candidates for the report: at most three per audit and none overlapping an earlier one.
                    // With the AI step enabled, the AI picks the ones that matter most to a visitor (Grader).
                    $overlaps = array_filter($tops, static fn (int $top): bool => abs($top - $rect['top']) < 60);

                    if ($n < 8 && $perAudit < 3 && [] === $overlaps) {
                        $crop = $this->screenshots->cropMarked($id, 'home-mobile-full.jpg', $fullSaved['scale'], $rect, 'finding-'.($n + 1));

                        if (null !== $crop) {
                            $data['crops'][] = ['ref' => 'f'.($n + 1), 'file' => $crop['file'], 'width' => $crop['width'], 'height' => $crop['height'], 'auditId' => $auditId, 'title' => $audit['title'], 'text' => (string) ($item['text'] ?? ''), 'explanation' => (string) ($item['explanation'] ?? ''), 'ratio' => preg_match('/contrast of ([0-9.]+)/', (string) ($item['explanation'] ?? ''), $cm) ? (float) $cm[1] : null];
                            $tops[] = $rect['top'];
                            ++$n;
                            ++$perAudit;
                        }
                    }

                    if (\count($data['pins']) < 5 && $rect['top'] + $rect['height'] / 2 < $viewportCss) {
                        $data['pins'][] = [
                            'x' => round(($rect['left'] + $rect['width'] / 2) / max(1, $full['width']) * 100, 1),
                            'y' => round(($rect['top'] + $rect['height'] / 2) / max(1, $viewportCss) * 100, 1),
                            'kind' => 'warn',
                            'label' => $audit['title'],
                        ];
                    }
                }
            }
        }

        unset($mobile['screenshots'], $desktop['screenshots']);
        $data['psi']['home_mobile'] = $mobile;
        $data['psi']['home_desktop'] = $desktop;

        return $data;
    }

    private function speedPages(array $data, string $locale): array
    {
        $jobs = [];
        $preferred = ['offer', 'contact', 'about', 'other', 'legal'];
        $pages = (array) ($data['pages'] ?? []);
        usort($pages, static fn (array $a, array $b): int => (int) array_search($a['role'], $preferred, true) <=> (int) array_search($b['role'], $preferred, true));
        $i = 0;

        foreach ($pages as $page) {
            if ('home' === $page['role'] || 'legal' === $page['role']) {
                continue;
            }

            ++$i;
            $jobs['page_'.$i] = ['url' => $page['url'], 'strategy' => 'mobile'];

            if (2 === $i) {
                break;
            }
        }

        if ([] === $jobs) {
            return $data;
        }

        foreach ($this->pageSpeed->runMany($jobs, $locale) as $key => $result) {
            unset($result['screenshots']);
            $data['psi'][$key] = $result;
        }

        return $data;
    }

    private function analyze(array $data): array
    {
        $homeUrl = (string) $data['home']['url'];
        $urls = [];

        foreach ((array) $data['pages'] as $page) {
            if ($page['url'] !== $homeUrl) {
                $urls[] = $page['url'];
            }
        }

        $homeHtml = $data['html'][$homeUrl] ?? [];
        $legalLinks = (array) ($homeHtml['links']['legal'] ?? []);

        foreach ($legalLinks as $legalUrl) {
            $urls[] = $legalUrl;
        }

        $responses = [] === $urls ? [] : $this->fetcher->fetchAll(array_values(array_unique($urls)));

        foreach ((array) $data['pages'] as $page) {
            $r = $responses[$page['url']] ?? null;

            if (null === $r || $r['status'] >= 400 || '' === $r['body']) {
                continue;
            }

            $analysis = $this->htmlAnalyzer->analyze($r['url'], $r['body']);
            $analysis['status'] = $r['status'];
            $analysis['time'] = $r['time'];
            $data['html'][$page['url']] = $analysis;
        }

        $legalStatus = [];

        foreach ($legalLinks as $kind => $legalUrl) {
            $legalStatus[$kind] = $responses[$legalUrl]['status'] ?? 0;
        }

        $thirdParties = (array) ($data['psi']['home_mobile']['network']['thirdParties'] ?? []);
        $data['legal'] = $this->legalAnalyzer->analyze($data['html'], $thirdParties, $legalStatus);
        $data['aiVisibility'] = $this->aiVisibilityAnalyzer->analyze($data['robots'] ?? ['status' => 0, 'body' => ''], $data['llms'] ?? ['status' => 0, 'body' => '', 'headers' => []], $data['llmsFull'] ?? ['status' => 0, 'body' => '', 'headers' => []], $homeHtml);

        return $data;
    }

    private function ai(int $id, array $data, string $locale): array
    {
        if ($this->aiReviewer->isEnabled()) {
            $pages = [];

            foreach ((array) $data['pages'] as $page) {
                $html = $data['html'][$page['url']] ?? null;

                if (null === $html || 'legal' === $page['role']) {
                    continue;
                }

                $pages[] = ['url' => $page['url'], 'role' => $page['role'], 'title' => $html['title'], 'text' => $html['text']];
            }

            $shot = isset($data['screenshots']['mobile']['file']) ? $this->screenshots->path($id, $data['screenshots']['mobile']['file']) : null;
            $data['ai'] = $this->aiReviewer->review((string) $data['home']['domain'], $pages, $shot, $locale, (array) ($data['crops'] ?? []));

            if (isset($data['ai']['error'])) {
                $this->logger?->warning(sprintf('Website check #%d: AI review failed: %s', $id, $data['ai']['error']));
                $data['errors']['ai'] = $data['ai']['error'];
            }
        }

        // The page texts were only needed for the AI step: keep a short excerpt.
        foreach ($data['html'] as &$html) {
            $html['text'] = mb_substr((string) $html['text'], 0, 600);
        }

        unset($html);

        return $data;
    }

    private function finish(array $data, string $locale): array
    {
        // Findings and measures in the check's language, also where the bundle ships
        // no translation file (translated once by the AI, see AiCatalogue).
        if ($this->translator instanceof TranslatorBagInterface && $this->catalogue->isMissing($this->translator, $locale)) {
            $this->catalogue->generate($this->translator, $locale);
        }

        $data['_result'] = $this->grader->grade($data, $locale);

        return $data;
    }

    // -------------------------------------------------------------- helpers

    /**
     * @return array{0: list<string>, 1: list<string>} page URLs, child sitemap URLs
     */
    private function parseSitemap(string $xml): array
    {
        $urls = [];
        $children = [];

        if (!str_contains($xml, '<')) {
            return [[], []];
        }

        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        if (false === $doc) {
            return [[], []];
        }

        $name = $doc->getName();

        foreach ($doc->children() as $child) {
            $loc = trim((string) ($child->loc ?? ''));

            if ('' === $loc) {
                continue;
            }

            if ('sitemapindex' === $name) {
                $children[] = $loc;
            } else {
                $urls[] = $loc;
            }
        }

        return [$urls, $children];
    }

    public function plainSummary(array $result): string
    {
        $lines = [sprintf('%s: %s %d (%s)', $result['domain'], 'Grade', $result['grade'], $result['gradeWord'])];

        foreach ($result['areas'] as $area) {
            $lines[] = sprintf('%s: %s – %s', $area['label'], $area['grade'] > 0 ? $area['grade'] : '–', $area['summary']);
        }

        if ([] !== $result['measures']) {
            $lines[] = '';

            foreach ($result['measures'] as $i => $m) {
                $lines[] = sprintf('%d. %s (%s)', $i + 1, $m['title'], $m['effort']);
            }
        }

        return implode("\n", $lines);
    }
}
