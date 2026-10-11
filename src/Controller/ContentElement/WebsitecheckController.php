<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Altcha\Altcha;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\PageModel;
use Neo\WebsitecheckBundle\Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * Content element "Website check": the whole flow (URL → progress → lead form
 * → result) lives in the Twig template and the bundle's JavaScript; this
 * controller only passes configuration (endpoints, limits, texts, links).
 */
#[AsContentElement('neo_websitecheck', category: 'neo', template: 'content_element/neo_websitecheck')]
final class WebsitecheckController extends AbstractContentElementController
{
    public function __construct(
        private readonly Settings $settings,
        private readonly RouterInterface $router,
        private readonly ContentUrlGenerator $urlGenerator,
        private readonly InsertTagParser $insertTagParser,
        private readonly Altcha|null $altcha = null,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $GLOBALS['TL_CSS']['neo_websitecheck'] = 'bundles/neowebsitecheck/websitecheck.css|static';
        $GLOBALS['TL_JAVASCRIPT']['neo_websitecheck'] = 'bundles/neowebsitecheck/websitecheck.js|async|static';

        $page = $request->attributes->get('pageModel');
        $lang = $page instanceof PageModel ? (string) $page->language : ($request->getLocale() ?: 'en');

        $brand = $this->settings->brand();
        // Configuration: the one picked in the element (core "module" column) or the one matching the page language.
        $configRow = $this->settings->db()?->config((int) ($model->row()['module'] ?? 0), $lang);
        $configId = (int) ($configRow['id'] ?? 0);
        // Multilingual sites: contact and privacy page of this language's configuration
        // first, then the global ones from the settings / config.yaml.
        $contactUrl = $this->pageUrl((int) ($configRow['contact_page'] ?? 0)) ?: $this->pageUrl((int) $brand['contact_page']) ?: (string) $brand['contact_url'];
        $privacyUrl = $this->pageUrl((int) ($configRow['privacy_page'] ?? 0)) ?: $this->pageUrl((int) $brand['privacy_page']) ?: (string) $brand['privacy_url'];

        // Core "url" field: {{link_url::42}} from the page picker or a typed address
        $elementUrl = trim((string) ($model->row()['url'] ?? ''));

        if ('' !== $elementUrl) {
            $contactUrl = trim($this->insertTagParser->replaceInline($elementUrl)) ?: $contactUrl;
        }

        // No inline colours: the element ships a neutral look and is themed with
        // the --nwc-* custom properties in the site's stylesheet (inline values
        // would beat the theme). The brand colours style PDF and e-mail only.
        $style = '';

        $config = [
            'start' => $this->router->generate('neo_websitecheck_start'),
            'status' => $this->router->generate('neo_websitecheck_status', ['id' => 0]),
            'claim' => $this->router->generate('neo_websitecheck_claim', ['id' => 0]),
            'challenge' => null !== $this->altcha ? $this->router->generate('neo_websitecheck_challenge') : null,
            'altchaMax' => null !== $this->altcha ? $this->altcha->getRangeMax() : 0,
            'lang' => $lang,
            'limit' => $this->settings->checksPerIpPerDay(),
            'limitHour' => $this->settings->checksPerIpPerHour(),
            'dataLayer' => $this->settings->dataLayer(),
            'contactUrl' => $contactUrl,
            'config' => $configId,
        ];

        $template->set('config', $config);
        $template->set('lang', $lang);
        $template->set('text', (string) ($model->row()['text'] ?? ''));
        $template->set('contact_url', $contactUrl);
        $template->set('privacy_url', $privacyUrl);
        $template->set('style', $style);
        $template->set('vars', ['company' => (string) $brand['name'], 'owner' => (string) $brand['owner_name'], 'phone' => (string) $brand['phone'], 'website' => (string) $brand['website_url'], 'days' => (string) $this->settings->retentionDays(), 'config_id' => $configId]);
        $template->set('config_id', $configId);
        // Result on the page: short (grade, one or two sentences, PDF hint) or the full overview.
        $template->set('result_mode', 'full' === ($configRow['fe_result'] ?? '') ? 'full' : 'compact');
        $template->set('newsletter', $this->settings->newsletterEnabled($configId, $lang));
        $template->set('newsletter_label', (string) ($this->settings->newsletter()['label'] ?? ''));
        $template->set('altcha', null !== $this->altcha);
        $template->set('element_id', 'wc'.(int) ($model->row()['id'] ?? 0));

        return $template->getResponse();
    }

    private function pageUrl(int $id): string
    {
        if ($id <= 0) {
            return '';
        }

        try {
            $page = PageModel::findPublishedById($id);

            return null === $page ? '' : $this->urlGenerator->generate($page);
        } catch (\Throwable) {
            return '';
        }
    }
}
