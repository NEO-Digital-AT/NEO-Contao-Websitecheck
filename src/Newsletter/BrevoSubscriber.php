<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Newsletter;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\PageModel;
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
        private readonly ContaoFramework $framework,
        private readonly LoggerInterface|null $logger = null,
    ) {
    }

    public function isEnabled(int $configId = 0, string $locale = ''): bool
    {
        return $this->settings->newsletterEnabled($configId, $locale);
    }

    /**
     * Double opt-in with the list, template and confirmation page of the check's
     * configuration (= its language on multilingual sites).
     */
    public function subscribe(string $email, string $firstName, string $lastName, int $configId = 0, string $locale = ''): bool
    {
        if (!$this->isEnabled($configId, $locale)) {
            return false;
        }

        $nl = $this->settings->newsletterFor($configId, $locale);
        $redirectUrl = '';

        if ((int) $nl['brevo_redirect_page'] > 0) {
            $this->framework->initialize();
            $redirectUrl = (string) PageModel::findByPk((int) $nl['brevo_redirect_page'])?->getAbsoluteUrl();
        }

        try {
            $response = $this->httpClient->request('POST', 'https://api.brevo.com/v3/contacts/doubleOptinConfirmation', [
                'headers' => ['api-key' => $nl['brevo_api_key'], 'Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'json' => [
                    'email' => $email,
                    'attributes' => ['VORNAME' => $firstName, 'NACHNAME' => $lastName, 'FIRSTNAME' => $firstName, 'LASTNAME' => $lastName],
                    'includeListIds' => [(int) $nl['brevo_list_id']],
                    'templateId' => (int) $nl['brevo_template_id'],
                    'redirectionUrl' => $redirectUrl ?: (string) $nl['brevo_redirect_url'],
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
