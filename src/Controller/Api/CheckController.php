<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Controller\Api;

use Contao\CoreBundle\Altcha\Altcha;
use Neo\WebsitecheckBundle\Check\CheckRepository;
use Neo\WebsitecheckBundle\Check\CheckRunner;
use Neo\WebsitecheckBundle\Check\PageSpeedClient;
use Neo\WebsitecheckBundle\Check\UrlNormalizer;
use Neo\WebsitecheckBundle\Mail\ReportMailer;
use Neo\WebsitecheckBundle\Newsletter\Subscriber;
use Neo\WebsitecheckBundle\Settings;
use Neo\WebsitecheckBundle\Text\TextProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * JSON endpoints used by the content element's JavaScript:
 *
 *  GET  /_neo/websitecheck/challenge       ALTCHA challenge (when Contao ≥ 5.7 provides the service)
 *  POST /_neo/websitecheck/start           {url, altcha?, lang} → {id, token, status}
 *  GET  /_neo/websitecheck/{id}/status     advances the check by one step when idle, returns progress
 *  POST /_neo/websitecheck/{id}/claim      {token, firstname, lastname, email, newsletter} → result, sends the e-mails
 *  GET  /_neo/websitecheck/{id}/image/…    screenshots for the live preview (token-protected)
 *
 * Every row is protected by a random token handed out at start, so ids
 * cannot be enumerated. No framework boot: DBAL + plain Symfony only.
 */
final class CheckController
{
    public function __construct(
        private readonly CheckRepository $repository,
        private readonly CheckRunner $runner,
        private readonly UrlNormalizer $normalizer,
        private readonly ReportMailer $mailer,
        private readonly Subscriber $newsletter,
        private readonly Settings $settings,
        private readonly RouterInterface $router,
        private readonly Altcha|null $altcha = null,
        private readonly LoggerInterface|null $logger = null,
        private readonly TextProvider|null $texts = null,
        private readonly PageSpeedClient|null $pageSpeed = null,
    ) {
    }

    public function challenge(): JsonResponse
    {
        if (null === $this->altcha) {
            return $this->json(['error' => 'altcha_unavailable'], 404);
        }

        return $this->json($this->altcha->createChallenge()->toArray());
    }

    public function start(Request $request): JsonResponse
    {
        $payload = $this->payload($request);
        $normalized = $this->normalizer->normalize((string) ($payload['url'] ?? ''));

        if (null === $normalized) {
            return $this->json(['error' => 'invalid_url'], 400);
        }

        if (!$this->spamCheckPassed($payload)) {
            return $this->json(['error' => 'spam_check'], 400);
        }

        // Without a PageSpeed key there are no measurements, screenshots or grades
        // worth sending: refuse openly instead of producing an empty report.
        if (null !== $this->pageSpeed && !$this->pageSpeed->isConfigured()) {
            $this->logger?->error('Website check refused: no PageSpeed key (Website check settings → Keys and AI).');

            return $this->json(['error' => 'unavailable'], 503);
        }

        $ipHash = $this->ipHash($request);

        // Per visitor: a short window against bursts (cost, abuse), a day window on top.
        if ($this->repository->countForIp($ipHash, 3600) >= $this->settings->checksPerIpPerHour()) {
            return $this->json(['error' => 'rate_limited', 'window' => 'hour', 'limit' => $this->settings->checksPerIpPerHour()], 429);
        }

        if ($this->repository->countForIp($ipHash, 86400) >= $this->settings->checksPerIpPerDay()) {
            return $this->json(['error' => 'rate_limited', 'window' => 'day', 'limit' => $this->settings->checksPerIpPerDay()], 429);
        }

        $locale = $this->locale($request, $payload);
        $cached = $this->repository->findRecentDone($normalized['url'], $this->settings->cacheHours(), $locale);

        // Never hand out a result that lacks the measurements (e.g. made while a key was missing).
        if (null !== $cached && (empty($cached['data']['psi']['home_mobile']) || !empty($cached['data']['psi']['home_mobile']['error']))) {
            $cached = null;
        }
        // Configuration (texts, e-mail, PDF design) chosen by the element; falls back to the language's record.
        $config = $this->settings->db()?->config((int) ($payload['config'] ?? 0), $locale);
        $configId = (int) ($config['id'] ?? 0);

        $id = null !== $cached
            ? $this->repository->createFromCache($cached, $locale, $ipHash, $configId)
            : $this->repository->create($normalized['domain'], $normalized['url'], $locale, $ipHash, $configId);

        $row = $this->repository->find($id);

        return $this->json($this->statusPayload($row) + ['token' => $row['token'], 'cached' => null !== $cached]);
    }

    public function status(int $id, Request $request): JsonResponse
    {
        $row = $this->repository->findWithToken($id, (string) $request->query->get('token', ''));

        if (null === $row) {
            return $this->json(['error' => 'not_found'], 404);
        }

        if (\in_array($row['status'], [CheckRepository::STATUS_QUEUED, CheckRepository::STATUS_RUNNING], true)) {
            $row = $this->runner->advance($id) ?? $row;
        }

        return $this->json($this->statusPayload($row));
    }

    public function claim(int $id, Request $request): JsonResponse
    {
        $payload = $this->payload($request);
        $row = $this->repository->findWithToken($id, (string) ($payload['token'] ?? ''));

        if (null === $row) {
            return $this->json(['error' => 'not_found'], 404);
        }

        $first = $this->clean((string) ($payload['firstname'] ?? ''), 100);
        $last = $this->clean((string) ($payload['lastname'] ?? ''), 100);
        $email = filter_var(trim((string) ($payload['email'] ?? '')), FILTER_VALIDATE_EMAIL);

        if ('' === $first || '' === $last || false === $email) {
            return $this->json(['error' => 'invalid_lead'], 400);
        }

        if (CheckRepository::STATUS_DONE !== $row['status']) {
            if (CheckRepository::STATUS_FAILED === $row['status']) {
                return $this->json(['error' => 'failed'], 409); // the reason stays in the back end
            }

            return $this->json(['error' => 'not_ready'] + $this->statusPayload($row), 409);
        }

        $newsletter = !empty($payload['newsletter']) && $this->settings->newsletterEnabled((int) $row['config_id'], (string) $row['locale']);
        $contact = !empty($payload['contact']) && $this->settings->askContact((int) $row['config_id'], (string) $row['locale']);

        if (!$row['claimed']) {
            $update = [
                'firstname' => $first,
                'lastname' => $last,
                'email' => $email,
                'contact' => $contact ? '1' : '',
                'newsletter' => $newsletter ? '1' : '',
                'claimed' => time(),
            ];

            if ($contact) {
                // Proof of consent: the wording the visitor saw and when they agreed.
                $data = $row['data'];
                $data['consent']['contact'] = [
                    'time' => time(),
                    'text' => (string) $this->texts?->get('fe.contact', (string) $row['locale'], ['config_id' => (int) $row['config_id']]),
                ];
                $update['data'] = $data;
            }

            $this->repository->update($id, $update);
            $row = $this->repository->find($id);
        }

        $mailSent = (int) $row['mail_sent'] > 0;

        if (!$mailSent) {
            try {
                $this->mailer->send($row);
                $mailSent = true;
            } catch (\Throwable $e) {
                $this->logger?->error(sprintf('Website check #%d: report mail failed: %s', $id, $e->getMessage()));
                $data = $row['data'];
                $data['errors']['mail'] = mb_substr($e->getMessage(), 0, 500);
                $this->repository->update($id, ['data' => $data]);
            }
        }

        if ($newsletter && empty($row['data']['newsletterDone'])) {
            // the address of the first claim, never one sent with a later request for the same check
            if ($this->newsletter->subscribe((string) $row['email'], (string) $row['firstname'], (string) $row['lastname'], (int) $row['config_id'], (string) $row['locale'])) {
                $data = $this->repository->find($id)['data'];
                $data['newsletterDone'] = true;
                $this->repository->update($id, ['data' => $data]);
            }
        }

        return $this->json([
            'status' => 'done',
            'mailSent' => $mailSent,
            'result' => $this->publicResult($row),
        ]);
    }

    public function image(int $id, string $name, Request $request): Response
    {
        $row = $this->repository->findWithToken($id, (string) $request->query->get('token', ''));

        if (null === $row) {
            return new Response('', 404);
        }

        $path = $this->repository->dir($id).'/'.basename($name);

        if (!is_file($path)) {
            return new Response('', 404);
        }

        $response = new BinaryFileResponse($path);
        $response->setPrivate();
        $response->setMaxAge(3600);

        return $response;
    }

    // -------------------------------------------------------------- helpers

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function statusPayload(array $row): array
    {
        $progress = $this->runner->progress($row);
        // The preview window is a desktop browser: desktop first screen, mobile as fallback.
        $shot = $row['data']['screenshots']['desktop']['file'] ?? $row['data']['screenshots']['mobile']['file'] ?? null;

        return [
            'id' => (int) $row['id'],
            'status' => $row['status'],
            'domain' => $row['domain'],
            'step' => $row['step'],
            'steps' => $progress['steps'],
            'progress' => $progress['progress'],
            'eta' => $progress['eta'],
            'claimed' => (int) $row['claimed'] > 0,
            'screenshot' => null !== $shot ? $this->router->generate('neo_websitecheck_image', ['id' => $row['id'], 'name' => $shot, 'token' => $row['token']]) : null,
            'pins' => \array_slice((array) ($row['data']['pins'] ?? []), 0, 5),
            // only the fact; the technical reason (paths, hosts, messages) stays in the back end
            'error' => CheckRepository::STATUS_FAILED === $row['status'] ? 'failed' : null,
        ];
    }

    /**
     * The on-page result: everything the visitor sees, nothing internal.
     *
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function publicResult(array $row): array
    {
        $r = (array) $row['result'];

        return [
            'domain' => $r['domain'] ?? $row['domain'],
            'grade' => $r['grade'] ?? 0,
            'gradeWord' => $r['gradeWord'] ?? '',
            'verdict' => $r['verdict'] ?? '',
            'verdictKey' => $r['verdictKey'] ?? 'mid',
            'summary' => $r['summary'] ?? '',
            'scores' => $r['scores'] ?? [],
            'areas' => array_map(static fn (array $a): array => ['key' => $a['key'], 'label' => $a['label'], 'grade' => $a['grade'], 'summary' => $a['summary']], $r['areas'] ?? []),
            'measures' => $r['measures'] ?? [],
            'strengths' => $r['strengths'] ?? [],
            'pagesChecked' => $r['pagesChecked'] ?? 0,
        ];
    }

    /**
     * ALTCHA when Contao provides it (5.7+), otherwise a honeypot + minimum
     * fill time. The rate limit per IP applies in both cases.
     *
     * @param array<string, mixed> $payload
     */
    private function spamCheckPassed(array $payload): bool
    {
        if ('' !== trim((string) ($payload['website'] ?? ''))) {
            return false; // honeypot
        }

        if (null !== $this->altcha) {
            $solution = (string) ($payload['altcha'] ?? '');

            if ('' === $solution) {
                return false;
            }

            try {
                return $this->altcha->validate($solution);
            } catch (\Throwable) {
                return false;
            }
        }

        $started = (int) ($payload['started'] ?? 0);

        return $started > 0 && (time() * 1000 - $started) > 1500;
    }

    private function ipHash(Request $request): string
    {
        return hash('sha256', (string) $request->getClientIp().'|'.date('Y-m-d').'|neo_websitecheck');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function locale(Request $request, array $payload): string
    {
        $lang = strtolower(trim((string) ($payload['lang'] ?? '')));

        if (1 === preg_match('/^[a-z]{2,3}(?:[-_][a-z0-9]{2,8}){0,3}$/', $lang) && \strlen($lang) <= 32) {
            return $lang;
        }

        return $request->getLocale() ?: 'en';
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        $json = json_decode((string) $request->getContent(), true);

        if (\is_array($json)) {
            return $json;
        }

        return $request->request->all();
    }

    private function clean(string $value, int $max): string
    {
        $value = trim(strip_tags($value));
        $value = preg_replace('/[\r\n\t]+/', ' ', $value) ?? $value;

        return mb_substr($value, 0, $max);
    }

    private function json(array $data, int $status = 200): JsonResponse
    {
        $response = new JsonResponse($data, $status);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
