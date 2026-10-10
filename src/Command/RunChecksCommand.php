<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Command;

use Neo\WebsitecheckBundle\Check\CheckRepository;
use Neo\WebsitecheckBundle\Check\CheckRunner;
use Neo\WebsitecheckBundle\Check\UrlNormalizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * neo:websitecheck:run            — advances every pending check (cron/worker use)
 * neo:websitecheck:run <id>       — drives one check to the end
 * neo:websitecheck:run --url=…    — creates and runs a check from the CLI (testing the setup)
 */
#[AsCommand(name: 'neo:websitecheck:run', description: 'Run pending website checks, one check by id, or a new check for a URL.')]
final class RunChecksCommand extends Command
{
    public function __construct(
        private readonly CheckRepository $repository,
        private readonly CheckRunner $runner,
        private readonly UrlNormalizer $normalizer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::OPTIONAL, 'Id of one check to run to completion')
            ->addOption('url', null, InputOption::VALUE_REQUIRED, 'Create a new check for this website and run it (setup test)')
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Locale for a new check', 'en')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print the result as JSON');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $url = (string) $input->getOption('url');
        $id = (int) $input->getArgument('id');

        if ('' !== $url) {
            $normalized = $this->normalizer->normalize($url);

            if (null === $normalized) {
                $io->error('Not a public website URL: '.$url);

                return Command::FAILURE;
            }

            $id = $this->repository->create($normalized['domain'], $normalized['url'], (string) $input->getOption('locale'), 'cli');
            $io->writeln(sprintf('Created check #%d for %s', $id, $normalized['url']));
        }

        if ($id > 0) {
            $row = $this->runOne($id, $io);

            if (null === $row) {
                $io->error('Unknown check id '.$id);

                return Command::FAILURE;
            }

            if ($input->getOption('json')) {
                $output->writeln(json_encode($row['result'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            } else {
                $io->writeln((string) $row['summary']);

                if ([] !== ($row['data']['errors'] ?? [])) {
                    $io->warning('Steps with errors: '.json_encode($row['data']['errors'], JSON_UNESCAPED_SLASHES));
                }
            }

            return CheckRepository::STATUS_DONE === $row['status'] ? Command::SUCCESS : Command::FAILURE;
        }

        $ids = $this->repository->pendingIds();

        if ([] === $ids) {
            $io->writeln('No pending checks.');

            return Command::SUCCESS;
        }

        foreach ($ids as $pending) {
            $row = $this->runner->advance($pending);
            $io->writeln(sprintf('#%d %s → %s/%s', $pending, $row['domain'] ?? '', $row['status'] ?? '?', $row['step'] ?? ''));
        }

        return Command::SUCCESS;
    }

    private function runOne(int $id, SymfonyStyle $io): array|null
    {
        $row = $this->repository->find($id);

        if (null === $row) {
            return null;
        }

        while (\in_array($row['status'], [CheckRepository::STATUS_QUEUED, CheckRepository::STATUS_RUNNING], true)) {
            $before = $row['step'];
            $row = $this->runner->advance($id);

            if (null === $row) {
                return null;
            }

            $io->writeln(sprintf('  step %-12s %5.1fs  %s', $row['step'], (float) ($row['step_times'][$row['step']] ?? 0), $row['status']));

            if ($before === $row['step'] && 0 === (int) $row['locked_until'] && CheckRepository::STATUS_RUNNING === $row['status'] && (int) $row['attempts'] > 0) {
                continue; // retry of the same step
            }
        }

        return $row;
    }
}
