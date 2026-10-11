<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Newsletter;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\OptIn\OptInInterface;
use Contao\CoreBundle\String\SimpleTokenParser;
use Contao\Database;
use Contao\NewsletterChannelModel;
use Contao\NewsletterRecipientsModel;
use Contao\PageModel;
use Contao\System;
use Neo\WebsitecheckBundle\Settings;
use Psr\Log\LoggerInterface;

/**
 * Double opt-in through Contao's own newsletter module — used when the NEO
 * Brevo module is not installed. Works exactly like Contao's "Subscribe"
 * module: an inactive recipient in the configuration's channel, an opt-in token
 * (contao.opt_in) and a confirmation e-mail whose link points to the
 * configuration's confirmation page. That page carries Contao's "Subscribe"
 * module, which activates the recipient when the link is clicked.
 *
 * The e-mail text is the site's own: the "Subscription message" of a Subscribe
 * module for that channel (so every language has its text), otherwise Contao's
 * default text. Failures are logged, never shown to the visitor.
 */
final class ContaoSubscriber
{
    public function __construct(
        private readonly Settings $settings,
        private readonly ContaoFramework $framework,
        private readonly OptInInterface|null $optIn = null,
        private readonly SimpleTokenParser|null $tokens = null,
        private readonly LoggerInterface|null $logger = null,
    ) {
    }

    public function subscribe(string $email, int $configId = 0, string $locale = ''): bool
    {
        if (null === $this->optIn || null === $this->tokens || !class_exists(NewsletterRecipientsModel::class)) {
            return false;
        }

        $nl = $this->settings->newsletterFor($configId, $locale);
        $channelId = (int) $nl['brevo_list_id'];

        try {
            $this->framework->initialize();

            $channel = NewsletterChannelModel::findById($channelId);
            $page = PageModel::findById((int) $nl['brevo_redirect_page']);

            if (null === $channel || null === $page) {
                $this->logger?->warning(\sprintf('Website check newsletter (Contao): channel %d or confirmation page %d not found.', $channelId, (int) $nl['brevo_redirect_page']));

                return false;
            }

            // Already an active subscriber of this channel: nothing to confirm.
            if (null !== NewsletterRecipientsModel::findBy(['email=? AND pid=? AND active=1'], [$email, $channelId])) {
                return true;
            }

            // Like Contao's Subscribe module: drop unconfirmed older sign-ups first.
            if (null !== ($old = NewsletterRecipientsModel::findOldSubscriptionsByEmailAndPids($email, [$channelId]))) {
                while ($old->next()) {
                    $old->delete();
                }
            }

            $recipient = new NewsletterRecipientsModel();
            $recipient->pid = $channelId;
            $recipient->tstamp = time();
            $recipient->email = $email;
            $recipient->active = false;
            $recipient->addedOn = time();
            $recipient->save();

            $token = $this->optIn->create('nl', $email, ['tl_newsletter_recipients' => [(int) $recipient->id]]);

            $lang = str_replace('-', '_', $locale ?: (string) $page->language);
            System::loadLanguageFile('default', $lang);
            System::loadLanguageFile('tl_module', $lang);

            $link = $page->getAbsoluteUrl();
            $host = (string) parse_url($link, PHP_URL_HOST);
            $link .= (str_contains($link, '?') ? '&' : '?').'token='.$token->getIdentifier();

            $data = [
                'token' => $token->getIdentifier(),
                'domain' => $host,
                'link' => $link,
                'channel' => (string) $channel->title,
                'channels' => (string) $channel->title,
            ];

            $token->send(
                \sprintf((string) ($GLOBALS['TL_LANG']['MSC']['nl_subject'] ?? 'Your subscription on %s'), $host),
                $this->tokens->parse($this->messageText($channelId, (int) $page->id), $data),
            );

            return true;
        } catch (\Throwable $e) {
            $this->logger?->warning('Website check newsletter (Contao) failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * The "Subscription message" of the site's Subscribe module for this channel
     * (preferably the one on the confirmation page), else Contao's default text.
     */
    private function messageText(int $channelId, int $pageId): string
    {
        $text = '';

        try {
            $modules = Database::getInstance()->prepare("SELECT id, nl_channels, nl_subscribe FROM tl_module WHERE type='subscribe' AND nl_subscribe IS NOT NULL AND nl_subscribe != ''")->execute()->fetchAllAssoc();
            $onPage = Database::getInstance()->prepare("SELECT c.module FROM tl_content c JOIN tl_article a ON a.id=c.pid AND c.ptable='tl_article' WHERE a.pid=? AND c.type='module'")->execute($pageId)->fetchEach('module');

            usort($modules, static fn (array $a, array $b): int => (int) \in_array($b['id'], $onPage, false) <=> (int) \in_array($a['id'], $onPage, false));

            foreach ($modules as $module) {
                $channels = array_map('intval', (array) unserialize((string) $module['nl_channels'], ['allowed_classes' => false]));

                if (\in_array($channelId, $channels, true)) {
                    $text = (string) $module['nl_subscribe'];
                    break;
                }
            }
        } catch (\Throwable) {
        }

        return '' !== trim($text) ? $text : (string) ($GLOBALS['TL_LANG']['tl_module']['text_subscribe'][1] ?? "##link##");
    }
}
