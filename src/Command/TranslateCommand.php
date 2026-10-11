<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Command;

use Neo\WebsitecheckBundle\I18n\AiCatalogue;
use Neo\WebsitecheckBundle\Lang;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(name: 'neo:websitecheck:translate', description: 'Translate the visitor-facing texts into a language without translation file (AI), optionally export them as an editable YAML file.')]
final class TranslateCommand extends Command
{
    public function __construct(
        private readonly AiCatalogue $catalogue,
        private readonly TranslatorInterface $translator,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('language', InputArgument::REQUIRED, 'Language code, e.g. fr, it, pt, pl')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Translate all texts again')
            ->addOption('export', null, InputOption::VALUE_NONE, 'Write translations/neo_websitecheck.<lang>.yaml to the project (it then wins over the AI texts and can be corrected by hand)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $lang = Lang::primary((string) $input->getArgument('language'));

        if ('' === $lang || !$this->translator instanceof TranslatorBagInterface) {
            $io->error('Unknown language code.');

            return self::FAILURE;
        }

        if ($this->catalogue->hasOwnCatalogue($this->translator, $lang)) {
            $io->success(sprintf('"%s" has its own translation file — nothing to do.', $lang));

            return self::SUCCESS;
        }

        $count = $this->catalogue->generate($this->translator, $lang, (bool) $input->getOption('force'));
        $total = \count($this->catalogue->messages($lang));
        $io->writeln(sprintf('%d texts translated, %d stored for "%s" (%s).', $count, $total, $lang, $this->catalogue->file($lang)));

        if (0 === $total) {
            $io->error('No texts available — are the AI settings (ai.api_key, ai.model) configured?');

            return self::FAILURE;
        }

        if ($input->getOption('export')) {
            $tree = [];

            foreach ($this->catalogue->messages($lang) as $key => $text) {
                $node = &$tree;

                foreach (explode('.', $key) as $part) {
                    $node = &$node[$part];
                }

                $node = $text;
                unset($node);
            }

            $file = $this->projectDir.'/translations/'.AiCatalogue::DOMAIN.'.'.$lang.'.yaml';
            @mkdir(\dirname($file), 0775, true);
            file_put_contents($file, "# AI translation (neo:websitecheck:translate), please review.\n".Yaml::dump($tree, 8, 4));
            $io->success('Exported to '.$file.' — clear the cache to use it.');
        }

        return self::SUCCESS;
    }
}
