<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Neo\WebsitecheckBundle\Check\CheckRepository;
use Neo\WebsitecheckBundle\Check\CheckRunner;
use Neo\WebsitecheckBundle\Settings;

/**
 * Daily: delete checks (lead data, screenshots, PDFs) older than the retention
 * period. Minutely: advance checks that a visitor abandoned mid-way, so a
 * started measurement is still finished and cached for the next visitor.
 */
final class PurgeChecksCron
{
    public function __construct(
        private readonly CheckRepository $repository,
        private readonly CheckRunner $runner,
        private readonly Settings $settings,
    ) {
    }

    #[AsCronJob('daily')]
    public function purge(): void
    {
        $this->repository->purge($this->settings->retentionDays());
    }

    #[AsCronJob('minutely')]
    public function advancePending(): void
    {
        foreach ($this->repository->pendingIds(3) as $id) {
            $this->runner->advance($id);
        }
    }
}
