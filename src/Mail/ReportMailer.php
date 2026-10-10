<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Mail;

use Contao\CoreBundle\Framework\ContaoFramework;
use Neo\WebsitecheckBundle\Check\CheckRepository;
use Neo\WebsitecheckBundle\Report\PdfGenerator;
use Neo\WebsitecheckBundle\Report\ReportRenderer;
use Neo\WebsitecheckBundle\Settings;
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
        $from = $this->from();

        $subject = $this->translator->trans('mail.subject', ['%domain%' => $row['domain'], '%grade%' => $result['grade'] ?? '', '%word%' => $result['gradeWord'] ?? ''], 'neo_websitecheck', $locale);

        $email = (new Email())
            ->from($from)
            ->to(new Address((string) $row['email'], trim($row['firstname'].' '.$row['lastname'])))
            ->subject($subject)
            ->html($this->renderer->renderMail($row, 'mail_visitor'))
            ->text($this->plain($row, $locale));

        if ('' !== $this->settings->mail()['reply_to']) {
            $email->replyTo($this->settings->mail()['reply_to']);
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
                ->subject($this->translator->trans('mail.owner_subject', ['%domain%' => $row['domain'], '%grade%' => $result['grade'] ?? '', '%name%' => trim($row['firstname'].' '.$row['lastname']), '%newsletter%' => '1' === (string) $row['newsletter'] ? '+NL' : ''], 'neo_websitecheck', $locale))
                ->html($this->renderer->renderMail($row, 'mail_owner'))
                ->text($this->plain($row, $locale, true));

            if (null !== $pdfPath) {
                $copy->attachFromPath($pdfPath, basename($pdfPath), 'application/pdf');
            }

            $this->mailer->send($copy);
        }

        $this->repository->update((int) $row['id'], ['mail_sent' => time()]);
    }

    private function from(): Address
    {
        $mail = $this->settings->mail();
        $address = trim($mail['from_email']);

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
        $t = fn (string $key, array $p = []): string => $this->translator->trans($key, $p, 'neo_websitecheck', $locale);
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
        $contact = $this->settings->brand()['contact_url'] ?: $this->settings->brand()['website_url'];

        if ('' !== $contact) {
            $lines[] = $contact;
        }

        $lines[] = '';
        $lines[] = trim($this->settings->brand()['owner_name'].' · '.($this->settings->brand()['name'] ?: '').' · '.$this->settings->brand()['phone'], ' ·');

        return implode("\n", $lines);
    }
}
