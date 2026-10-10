<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Newsletter;

use Neo\WebsitecheckBundle\Settings;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Optional newsletter opt-in via Brevo's double-opt-in endpoint: the visitor
 * only becomes a subscriber after clicking the confirmation link Brevo sends.
 * Failures are logged, never shown to the visitor (the report still arrives).
 */
final class BrevoSubscriber
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Settings $settings,
        private readonly LoggerInterface|null $logger = null,
    ) {
    }

    public function isEnabled(): bool
    {
        $nl = $this->settings->newsletter();

        return 'brevo' === $nl['provider'] && '' !== trim((string) $nl['brevo_api_key']) && (int) $nl['brevo_list_id'] > 0 && (int) $nl['brevo_template_id'] > 0;
    }

    public function subscribe(string $email, string $firstName, string $lastName, string $redirectUrl = ''): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $nl = $this->settings->newsletter();

        try {
            $response = $this->httpClient->request('POST', 'https://api.brevo.com/v3/contacts/doubleOptinConfirmation', [
                'headers' => ['api-key' => $nl['brevo_api_key'], 'Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'json' => [
                    'email' => $email,
                    'attributes' => ['VORNAME' => $firstName, 'NACHNAME' => $lastName, 'FIRSTNAME' => $firstName, 'LASTNAME' => $lastName],
                    'includeListIds' => [(int) $nl['brevo_list_id']],
                    'templateId' => (int) $nl['brevo_template_id'],
                    'redirectionUrl' => $redirectUrl ?: ($nl['brevo_redirect_url'] ?: $this->settings->brand()['website_url'] ?: 'https://example.com'),
                    'updateEnabled' => true,
                ],
                'timeout' => 15,
            ]);

            $status = $response->getStatusCode();

            if ($status >= 300) {
                $this->logger?->warning('Brevo double opt-in failed: HTTP '.$status.' '.$response->getContent(false));

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            $this->logger?->warning('Brevo double opt-in failed: '.$e->getMessage());

            return false;
        }
    }
}
