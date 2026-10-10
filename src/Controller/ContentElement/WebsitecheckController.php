<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Altcha\Altcha;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
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
        private readonly Altcha|null $altcha = null,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $GLOBALS['TL_CSS']['neo_websitecheck'] = 'bundles/neowebsitecheck/websitecheck.css|static';
        $GLOBALS['TL_JAVASCRIPT']['neo_websitecheck'] = 'bundles/neowebsitecheck/websitecheck.js|async|static';

        $page = $request->attributes->get('pageModel');
        $lang = $page instanceof PageModel ? (string) $page->language : ($request->getLocale() ?: 'en');

        $contactUrl = $this->settings->brand()['contact_url'];

        $jumpTo = (int) ($model->row()['jumpTo'] ?? 0);

        if ($jumpTo > 0) {
            $target = PageModel::findPublishedById($jumpTo);

            if (null !== $target) {
                try {
                    $contactUrl = $this->urlGenerator->generate($target);
                } catch (\Throwable) {
                    // keep the configured fallback
                }
            }
        }

        $config = [
            'start' => $this->router->generate('neo_websitecheck_start'),
            'status' => $this->router->generate('neo_websitecheck_status', ['id' => 0]),
            'claim' => $this->router->generate('neo_websitecheck_claim', ['id' => 0]),
            'challenge' => null !== $this->altcha ? $this->router->generate('neo_websitecheck_challenge') : null,
            'altchaMax' => null !== $this->altcha ? $this->altcha->getRangeMax() : 0,
            'lang' => $lang,
            'limit' => $this->settings->checksPerIpPerDay(),
            'dataLayer' => $this->settings->dataLayer(),
            'contactUrl' => $contactUrl,
        ];

        $template->set('config', $config);
        $template->set('lang', $lang);
        $template->set('text', (string) ($model->row()['text'] ?? ''));
        $template->set('contact_url', $contactUrl);
        $template->set('privacy_url', $this->settings->brand()['privacy_url']);
        $template->set('newsletter', $this->settings->newsletterEnabled());
        $template->set('newsletter_label', (string) ($this->settings->newsletter()['label'] ?? ''));
        $template->set('altcha', null !== $this->altcha);
        $template->set('element_id', 'wc'.(int) ($model->row()['id'] ?? 0));

        return $template->getResponse();
    }
}
