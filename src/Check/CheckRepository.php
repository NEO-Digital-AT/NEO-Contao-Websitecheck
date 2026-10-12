<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Check;

use Doctrine\DBAL\Connection;
use Neo\WebsitecheckBundle\Settings;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Persistence for tl_neo_websitecheck via Doctrine DBAL (no Contao framework
 * boot needed in the JSON endpoints). JSON columns "data" (raw measurements)
 * and "result" (graded, presentable outcome) are (de)serialised here.
 */
final class CheckRepository
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    public function __construct(
        private readonly Connection $db,
        private readonly Settings $settings,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): array|null
    {
        $row = $this->db->fetchAssociative('SELECT * FROM tl_neo_websitecheck WHERE id = ?', [$id]);

        return false === $row ? null : $this->hydrate($row);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findWithToken(int $id, string $token): array|null
    {
        $row = $this->find($id);

        if (null === $row || '' === $token || !hash_equals((string) $row['token'], $token)) {
            return null;
        }

        return $row;
    }

    public function create(string $domain, string $url, string $locale, string $ipHash, int $configId = 0): int
    {
        $now = time();

        $this->db->insert('tl_neo_websitecheck', [
            'tstamp' => $now,
            'created' => $now,
            'domain' => $domain,
            'url' => $url,
            'locale' => $locale,
            'config_id' => $configId,
            'status' => self::STATUS_QUEUED,
            'step' => '',
            'ip_hash' => $ipHash,
            'token' => bin2hex(random_bytes(20)),
            'data' => '{}',
            'result' => null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Copies the finished result of a recent check of the same domain into a
     * new row, so the visitor still has to leave their data but gets the
     * cached measurement instantly (and the PageSpeed quota is spared).
     */
    public function createFromCache(array $source, string $locale, string $ipHash, int $configId = 0): int
    {
        $now = time();

        $this->db->insert('tl_neo_websitecheck', [
            'tstamp' => $now,
            'created' => $now,
            'domain' => $source['domain'],
            'url' => $source['url'],
            'locale' => $locale,
            'config_id' => $configId,
            'status' => self::STATUS_DONE,
            'step' => 'finish',
            'finished' => $now,
            'ip_hash' => $ipHash,
            'token' => bin2hex(random_bytes(20)),
            'source_id' => (int) $source['id'],
            'data' => json_encode($source['data'], JSON_THROW_ON_ERROR),
            'result' => json_encode($source['result'], JSON_THROW_ON_ERROR),
            'grade' => (int) $source['grade'],
            'step_times' => json_encode($source['step_times'] ?? [], JSON_THROW_ON_ERROR),
        ]);

        $id = (int) $this->db->lastInsertId();

        // Screenshots and crops live on disk next to the id: share them by copying.
        // Never the PDF: it carries the first visitor's name and is rendered per check.
        $fs = new Filesystem();
        $from = $this->dir((int) $source['id']);

        if ($fs->exists($from)) {
            $fs->mkdir($this->dir($id));

            foreach (glob($from.'/*') ?: [] as $file) {
                if (is_file($file) && 'pdf' !== strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
                    $fs->copy($file, $this->dir($id).'/'.basename($file), true);
                }
            }
        }

        return $id;
    }

    /**
     * Finished check of the same domain within the cache window, if any.
     *
     * @return array<string, mixed>|null
     */
    public function findRecentDone(string $url, int $hours, string $locale = ''): array|null
    {
        if ($hours <= 0) {
            return null;
        }

        // Only a check in the same language can be reused: summaries, measures and
        // the AI reading are written in the language of the page it ran on.
        $lang = \Neo\WebsitecheckBundle\Lang::primary($locale);

        $row = $this->db->fetchAssociative(
            'SELECT * FROM tl_neo_websitecheck WHERE url = ? AND status = ? AND finished > ? AND (? = \'\' OR SUBSTRING_INDEX(REPLACE(LOWER(locale), \'_\', \'-\'), \'-\', 1) = ?) ORDER BY finished DESC LIMIT 1',
            [$url, self::STATUS_DONE, time() - $hours * 3600, $lang, $lang],
        );

        return false === $row ? null : $this->hydrate($row);
    }

    /**
     * Newest finished check (for previews).
     *
     * @return array<string, mixed>|null
     */
    public function findLatestDone(): array|null
    {
        $row = $this->db->fetchAssociative('SELECT * FROM tl_neo_websitecheck WHERE status = ? ORDER BY finished DESC LIMIT 1', [self::STATUS_DONE]);

        return false === $row ? null : $this->hydrate($row);
    }

    /** New (not cached) checks of all visitors within the last $seconds — these are the ones that cost. */
    public function countFresh(int $seconds): int
    {
        return (int) $this->db->fetchOne(
            'SELECT COUNT(*) FROM tl_neo_websitecheck WHERE source_id = 0 AND created > ?',
            [time() - $seconds],
        );
    }

    public function countForIp(string $ipHash, int $seconds): int
    {
        return (int) $this->db->fetchOne(
            'SELECT COUNT(*) FROM tl_neo_websitecheck WHERE ip_hash = ? AND created > ?',
            [$ipHash, time() - $seconds],
        );
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function update(int $id, array $fields): void
    {
        foreach (['data', 'result', 'step_times'] as $json) {
            if (\array_key_exists($json, $fields) && \is_array($fields[$json])) {
                $fields[$json] = json_encode($fields[$json], JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
            }
        }

        $fields['tstamp'] = time();
        $this->db->update('tl_neo_websitecheck', $fields, ['id' => $id]);
    }

    /**
     * Atomically takes the step lock: only one process may advance a check at
     * a time. Returns true when this call owns the lock.
     */
    public function lock(int $id, int $seconds): bool
    {
        $now = time();

        return 1 === $this->db->executeStatement(
            'UPDATE tl_neo_websitecheck SET locked_until = ?, step_started = ?, tstamp = ? WHERE id = ? AND locked_until < ? AND status IN (?, ?)',
            [$now + $seconds, $now, $now, $id, $now, self::STATUS_QUEUED, self::STATUS_RUNNING],
        );
    }

    public function unlock(int $id): void
    {
        $this->db->update('tl_neo_websitecheck', ['locked_until' => 0], ['id' => $id]);
    }

    /**
     * Ids of checks that still have work to do (for the console command / cron).
     *
     * @return list<int>
     */
    public function pendingIds(int $limit = 10): array
    {
        $ids = $this->db->fetchFirstColumn(
            'SELECT id FROM tl_neo_websitecheck WHERE status IN (?, ?) AND locked_until < ? ORDER BY created ASC LIMIT '.$limit,
            [self::STATUS_QUEUED, self::STATUS_RUNNING, time()],
        );

        return array_map('intval', $ids);
    }

    /**
     * Average duration per step over the last finished checks, used for the
     * "about 90 s left" display.
     *
     * @return array<string, float>
     */
    public function averageStepTimes(): array
    {
        $rows = $this->db->fetchFirstColumn(
            'SELECT step_times FROM tl_neo_websitecheck WHERE status = ? AND step_times IS NOT NULL AND source_id = 0 ORDER BY finished DESC LIMIT 20',
            [self::STATUS_DONE],
        );

        $sum = [];
        $count = [];

        foreach ($rows as $json) {
            $times = json_decode((string) $json, true);

            if (!\is_array($times)) {
                continue;
            }

            foreach ($times as $step => $seconds) {
                $sum[$step] = ($sum[$step] ?? 0) + (float) $seconds;
                $count[$step] = ($count[$step] ?? 0) + 1;
            }
        }

        $avg = [];

        foreach ($sum as $step => $total) {
            $avg[$step] = $total / max(1, $count[$step]);
        }

        return $avg;
    }

    /**
     * Deletes checks older than the retention period, including their files.
     */
    public function purge(int $days): int
    {
        $ids = $this->db->fetchFirstColumn('SELECT id FROM tl_neo_websitecheck WHERE created < ?', [time() - $days * 86400]);
        $fs = new Filesystem();

        foreach ($ids as $id) {
            $fs->remove($this->dir((int) $id));
        }

        if ([] === $ids) {
            return 0;
        }

        return (int) $this->db->executeStatement(
            'DELETE FROM tl_neo_websitecheck WHERE id IN ('.implode(',', array_map('intval', $ids)).')',
        );
    }

    public function deleteFiles(int $id): void
    {
        (new Filesystem())->remove($this->dir($id));
    }

    /** Directory for the screenshots and the PDF of one check. */
    public function dir(int $id): string
    {
        return $this->settings->storageDir().'/'.$id;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function hydrate(array $row): array
    {
        foreach (['data', 'result', 'step_times'] as $json) {
            $decoded = json_decode((string) ($row[$json] ?? ''), true);
            $row[$json] = \is_array($decoded) ? $decoded : [];
        }

        return $row;
    }
}
