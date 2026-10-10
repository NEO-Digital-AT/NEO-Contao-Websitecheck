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
     */
    public function send(array $row): void
    {
        $locale = (string) ($row['locale'] ?: 'en');
        $result = (array) $row['result'];
        $pdfPath = $this->pdf->generate($row);

        $vars = $this->renderer->context($row)['vars'];
        $subject = $this->texts->get('mail.subject', $locale, $vars);
        $from = $this->from($locale);

        $email = (new Email())
            ->from($from)
            ->to(new Address((string) $row['email'], trim($row['firstname'].' '.$row['lastname'])))
            ->subject($subject)
            ->html($this->renderer->renderMail($row, 'mail_visitor'))
            ->text($this->plain($row, $locale));

        $replyTo = $this->settings->db()?->text(substr($locale, 0, 2), 'mail_reply_to') ?: $this->settings->mail()['reply_to'];

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
                ->text($this->plain($row, $locale, true));

            if (null !== $pdfPath) {
                $copy->attachFromPath($pdfPath, basename($pdfPath), 'application/pdf');
            }

            $this->mailer->send($copy);
        }

        $this->repository->update((int) $row['id'], ['mail_sent' => time()]);
    }

    private function from(string $locale = 'en'): Address
    {
        $mail = $this->settings->mail();
        $lang = substr($locale, 0, 2);
        $address = trim($this->settings->db()?->text($lang, 'mail_from_email') ?: $mail['from_email']);
        $mail['from_name'] = $this->settings->db()?->text($lang, 'mail_from_name') ?: $mail['from_name'];

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
     * Plain-text alternative (also what text-only clients and spam filters read).
     *
     * @param array<string, mixed> $row
     */
    private function plain(array $row, string $locale, bool $owner = false): string
    {
        $result = (array) $row['result'];
        $vars = $this->renderer->context($row)['vars'];
        $t = fn (string $key, array $p = []): string => TextProvider::isEditable($key) ? $this->texts->get($key, $locale, $vars) : $this->translator->trans($key, $p, 'neo_websitecheck', $locale);
        $lines = [];

        if ($owner) {
            $lines[] = $t('mail.owner_lead', ['%name%' => trim($row['firstname'].' '.$row['lastname']), '%email%' => $row['email'], '%domain%' => $row['domain'], '%newsletter%' => $t('1' === (string) $row['newsletter'] ? 'word.yes' : 'word.no')]);
            $lines[] = '';
        }

        $lines[] = $t('mail.greeting', ['%name%' => $row['firstname']]);
        $lines[] = '';
        $lines[] = $t('mail.intro', ['%domain%' => $row['domain'], '%pages%' => $result['pagesChecked'] ?? 1]);
        $lines[] = '';
        $lines[] = $t('mail.grade_line', ['%grade%' => $result['grade'] ?? '', '%word%' => $result['gradeWord'] ?? '']).' '.($result['summary'] ?? '');
        $lines[] = '';

        if ([] !== ($result['measures'] ?? [])) {
            $lines[] = $t('mail.measures_title');

            foreach ($result['measures'] as $i => $m) {
                $lines[] = sprintf('%d. %s: %s', $i + 1, $m['title'], $m['text']);
            }

            $lines[] = '';
        }

        $lines[] = $t('mail.pdf_note');
        $lines[] = '';
        $lines[] = $t('mail.offer');
        $contact = (string) ($this->renderer->context($row)['contact_url'] ?? '');

        if ('' !== $contact) {
            $lines[] = $contact;
        }

        $lines[] = '';
        $lines[] = trim($this->settings->brand()['owner_name'].' · '.($this->settings->brand()['name'] ?: '').' · '.$this->settings->brand()['phone'], ' ·');

        return implode("\n", $lines);
    }
}
