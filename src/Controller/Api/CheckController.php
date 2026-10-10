<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Controller\Api;

use Contao\CoreBundle\Altcha\Altcha;
use Neo\WebsitecheckBundle\Check\CheckRepository;
use Neo\WebsitecheckBundle\Check\CheckRunner;
use Neo\WebsitecheckBundle\Check\UrlNormalizer;
use Neo\WebsitecheckBundle\Mail\ReportMailer;
use Neo\WebsitecheckBundle\Newsletter\BrevoSubscriber;
use Neo\WebsitecheckBundle\Settings;
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
        private readonly BrevoSubscriber $newsletter,
        private readonly Settings $settings,
        private readonly RouterInterface $router,
        private readonly Altcha|null $altcha = null,
        private readonly LoggerInterface|null $logger = null,
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

        $ipHash = $this->ipHash($request);

        if ($this->repository->countForIp($ipHash, 86400) >= $this->settings->checksPerIpPerDay()) {
            return $this->json(['error' => 'rate_limited', 'limit' => $this->settings->checksPerIpPerDay()], 429);
        }

        $locale = $this->locale($request, $payload);
        $cached = $this->repository->findRecentDone($normalized['domain'], $this->settings->cacheHours());

        $id = null !== $cached
            ? $this->repository->createFromCache($cached, $locale, $ipHash)
            : $this->repository->create($normalized['domain'], $normalized['url'], $locale, $ipHash);

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
                return $this->json(['error' => 'failed', 'message' => $row['error']], 409);
            }

            return $this->json(['error' => 'not_ready'] + $this->statusPayload($row), 409);
        }

        $newsletter = !empty($payload['newsletter']) && $this->settings->newsletterEnabled();

        if (!$row['claimed']) {
            $this->repository->update($id, [
                'firstname' => $first,
                'lastname' => $last,
                'email' => $email,
                'newsletter' => $newsletter ? '1' : '',
                'claimed' => time(),
            ]);
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
            if ($this->newsletter->subscribe($email, $first, $last)) {
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
        $shot = $row['data']['screenshots']['mobile']['file'] ?? null;

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
            'error' => CheckRepository::STATUS_FAILED === $row['status'] ? (string) $row['error'] : null,
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

        if (1 === preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $lang)) {
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
