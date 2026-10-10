<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Command;

use Neo\WebsitecheckBundle\Check\CheckRepository;
use Neo\WebsitecheckBundle\Check\CheckRunner;
use Neo\WebsitecheckBundle\Check\DemoData;
use Neo\WebsitecheckBundle\Check\Grader;
use Neo\WebsitecheckBundle\Mail\ReportMailer;
use Neo\WebsitecheckBundle\Report\PdfGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * neo:websitecheck:demo — stores a finished check from the bundled demo data
 * (no PageSpeed or AI key needed), renders its PDF and optionally sends the
 * report mail and the owner copy. Lets you test the whole pipeline — back-end
 * list, PDF, mail layout, your Mailpit/SMTP — right after installing.
 */
#[AsCommand(name: 'neo:websitecheck:demo', description: 'Create a finished demo check (PDF, optional e-mails) without any API keys.')]
final class DemoCheckCommand extends Command
{
    public function __construct(
        private readonly CheckRepository $repository,
        private readonly CheckRunner $runner,
        private readonly Grader $grader,
        private readonly DemoData $demo,
        private readonly PdfGenerator $pdf,
        private readonly ReportMailer $mailer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Language of the report (de, en, …)', 'en')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Visitor e-mail address of the demo check', 'maria@example.com')
            ->addOption('firstname', null, InputOption::VALUE_REQUIRED, 'Visitor first name')
            ->addOption('lastname', null, InputOption::VALUE_REQUIRED, 'Visitor last name')
            ->addOption('send', null, InputOption::VALUE_NONE, 'Also send the report e-mail (and the owner copy, if configured)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $locale = (string) $input->getOption('locale');

        $data = $this->demo->data();
        $result = $this->grader->grade($data, $locale);
        $result['crops'] = $this->demo->crops();

        $id = $this->repository->create((string) $result['domain'], (string) $result['url'], $locale, 'demo');

        $dir = $this->repository->dir($id);
        $fs = new Filesystem();
        $fs->mkdir($dir);

        foreach ($this->demo->files() as $file) {
            $fs->copy($this->demo->dir().'/'.$file, $dir.'/'.$file, true);
        }

        $now = time();
        $this->repository->update($id, [
            'status' => CheckRepository::STATUS_DONE,
            'step' => 'finish',
            'finished' => $now,
            'claimed' => $now,
            'data' => $data,
            'result' => $result,
            'grade' => (int) ($result['grade'] ?? 0),
            'summary' => $this->runner->plainSummary($result),
            'firstname' => (string) ($input->getOption('firstname') ?: $this->demo->firstName($locale)),
            'lastname' => (string) ($input->getOption('lastname') ?: $this->demo->lastName($locale)),
            'email' => (string) $input->getOption('email'),
            'newsletter' => '1',
        ]);

        $row = $this->repository->find($id);

        if (null === $row) {
            $io->error('Demo check could not be stored.');

            return Command::FAILURE;
        }

        $pdfPath = $this->pdf->generate($row, true);
        $io->writeln(sprintf('Demo check #%d for %s stored (grade %d)%s', $id, $row['domain'], (int) $row['grade'], null !== $pdfPath ? ', PDF: '.$pdfPath : ''));

        if ($input->getOption('send')) {
            $this->mailer->send($row);
            $this->repository->update($id, ['mail_sent' => time()]);
            $io->writeln('Report e-mail sent to '.$row['email'].'.');
        }

        return Command::SUCCESS;
    }
}
