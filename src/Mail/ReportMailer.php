<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Mail;

use Contao\CoreBundle\Framework\ContaoFramework;
use Neo\WebsitecheckBundle\Check\CheckRepository;
use Neo\WebsitecheckBundle\Report\PdfGenerator;
use Neo\WebsitecheckBundle\Report\ReportRenderer;
use Neo\WebsitecheckBundle\Settings;
use Neo\WebsitecheckBundle\Text\TextProvider;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Sends the report to the visitor (PDF attached) and, when configured, a
 * copy to the operator that additionally carries the lead data in subject and
 * body. Two separate e-mails on purpose: a Bcc could not show the operator
 * who requested the report.
 */
final class ReportMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly ReportRenderer $renderer,
        private readonly PdfGenerator $pdf,
        private readonly CheckRepository $repository,
        private readonly Settings $settings,
        private readonly TranslatorInterface $translator,
        private readonly ContaoFramework $framework,
        private readonly TextProvider $texts,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     * @param bool                 $freshPdf render the PDF again (resend from the back end: current design and texts)
     */
    public function send(array $row, bool $freshPdf = false): void
    {
        $locale = (string) ($row['locale'] ?: 'en');
        $result = (array) $row['result'];
        $pdfPath = $this->pdf->generate($row, $freshPdf);

        $vars = $this->renderer->context($row)['vars'];
        $subject = $this->texts->get('mail.subject', $locale, $vars);
        $from = $this->from($locale, $this->renderer->configFor($row));

        $html = $this->renderer->renderMail($row, 'mail_visitor');
        $email = (new Email())
            ->from($from)
            ->to(new Address((string) $row['email'], trim($row['firstname'].' '.$row['lastname'])))
            ->subject($subject)
            ->html($html)
            ->text($this->plain($row, $locale, false, $html));

        $config = $this->renderer->configFor($row);
        $replyTo = trim((string) ($config['mail_reply_to'] ?? '')) ?: $this->settings->mail()['reply_to'];

        if ('' !== $replyTo) {
            $email->replyTo($replyTo);
        }

        if (null !== $pdfPath) {
            $email->attachFromPath($pdfPath, basename($pdfPath), 'application/pdf');
        }

        $this->mailer->send($email);

        $notify = trim($this->settings->mail()['notify_email']);

        if ('' !== $notify) {
            $copy = (new Email())
                ->from($from)
                ->to($notify)
                ->replyTo(new Address((string) $row['email'], trim($row['firstname'].' '.$row['lastname'])))
                ->subject($this->texts->get('mail.owner_subject', $locale, $vars + ['firstname' => trim($row['firstname'].' '.$row['lastname'])]))
                ->html($this->renderer->renderMail($row, 'mail_owner'))
                ->text($this->plain($row, $locale, true, $html));

            if (null !== $pdfPath) {
                $copy->attachFromPath($pdfPath, basename($pdfPath), 'application/pdf');
            }

            $this->mailer->send($copy);
        }

        $this->repository->update((int) $row['id'], ['mail_sent' => time()]);
    }

    private function from(string $locale = 'en', array|null $config = null): Address
    {
        $mail = $this->settings->mail();
        $address = trim((string) ($config['mail_from_email'] ?? '')) ?: $mail['from_email'];
        $mail['from_name'] = trim((string) ($config['mail_from_name'] ?? '')) ?: $mail['from_name'];

        if ('' === $address) {
            $this->framework->initialize();
            $address = (string) $this->framework->getAdapter(\Contao\Config::class)->get('adminEmail');
        }

        if ('' === $address) {
            throw new \RuntimeException('No sender address: set neo_websitecheck.mail.from_email or the Contao admin e-mail.');
        }

        return new Address($address, $mail['from_name'] ?: ($this->settings->brand()['name'] ?: ''));
    }

    /**
     * Plain-text alternative (also what text-only clients and spam filters
     * read). Derived from the HTML that was actually designed, so it carries
     * the customer's words and nothing else; the operator copy is prefixed
     * with the lead line.
     *
     * @param array<string, mixed> $row
     */
    private function plain(array $row, string $locale, bool $owner = false, string|null $html = null): string
    {
        $text = self::htmlToText($html ?? $this->renderer->renderMail($row, 'mail_visitor'));

        if (!$owner) {
            return $text;
        }

        $lead = $this->translator->trans('mail.owner_lead', [
            '%name%' => trim($row['firstname'].' '.$row['lastname']),
            '%email%' => $row['email'],
            '%domain%' => $row['domain'],
            '%newsletter%' => $this->translator->trans('1' === (string) $row['newsletter'] ? 'word.yes' : 'word.no', [], 'neo_websitecheck', $locale),
        ], 'neo_websitecheck', $locale);

        $lead .= "\n".$this->translator->trans('mail.owner_contact', [], 'neo_websitecheck', $locale).': '
            .$this->translator->trans('1' === (string) ($row['contact'] ?? '') ? 'word.yes' : 'word.no', [], 'neo_websitecheck', $locale);

        return $lead."\n\n".$text;
    }

    /** Readable text version of an HTML e-mail: block ends become line breaks, links keep their address. */
    public static function htmlToText(string $html): string
    {
        $html = (string) preg_replace('#<(head|style|script|title)\b.*?</\1>#is', '', $html);
        $html = (string) preg_replace_callback('#<a\b[^>]*href=(["\'])(.*?)\1[^>]*>(.*?)</a>#is', static function (array $m): string {
            $label = trim(strip_tags($m[3]));
            $href = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return '' === $label || $label === $href || str_starts_with($href, 'mailto:') ? $label : $label.' ('.$href.')';
        }, $html);
        $html = (string) preg_replace('#<br\s*/?>#i', "\n", $html);
        $html = (string) preg_replace('#<li\b[^>]*>#i', "\n- ", $html);
        $html = (string) preg_replace('#</(p|div|h[1-6]|tr|ol|ul|table)>#i', "\n\n", $html);
        $html = (string) preg_replace('#</td>#i', ' ', $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{a0}", ' ', $text);
        $lines = array_map(static fn (string $l): string => trim((string) preg_replace('/[ \t]+/', ' ', $l)), explode("\n", $text));

        return trim((string) preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines)));
    }
}
