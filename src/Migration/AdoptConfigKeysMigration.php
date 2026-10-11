<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Neo\WebsitecheckBundle\Security\SecretCipher;
use Neo\WebsitecheckBundle\Settings;

/**
 * The API keys and the AI model live in the back end (settings
 * record, keys encrypted). Installations that had them in config.yaml / the
 * environment get them taken over once, so nobody has to type them again:
 * only empty back-end fields are filled, nothing stored there is overwritten.
 * The configuration stays the fallback for whatever is left empty.
 */
final class AdoptConfigKeysMigration extends AbstractMigration
{
    private const SECRET = ['psi_api_key', 'ai_api_key'];

    public function __construct(
        private readonly Connection $db,
        private readonly Settings $settings,
        private readonly SecretCipher $cipher,
    ) {
    }

    public function getName(): string
    {
        return 'NEO Website-Check: take over API keys and AI model from the configuration';
    }

    public function shouldRun(): bool
    {
        return [] !== $this->pending();
    }

    public function run(): MigrationResult
    {
        $set = $this->pending();

        if ((int) $this->db->fetchOne('SELECT COUNT(*) FROM tl_neo_websitecheck_settings') < 1) {
            $this->db->insert('tl_neo_websitecheck_settings', ['tstamp' => time()]);
        }

        foreach ($set as $column => $value) {
            $set[$column] = \in_array($column, self::SECRET, true) ? $this->cipher->encrypt($value) : $value;
        }

        $id = (int) $this->db->fetchOne('SELECT id FROM tl_neo_websitecheck_settings ORDER BY id ASC LIMIT 1');
        $this->db->update('tl_neo_websitecheck_settings', $set + ['tstamp' => time()], ['id' => $id]);
        $this->settings->db()?->reset();

        return $this->createResult(true, 'Taken over: '.implode(', ', array_keys($set)));
    }

    /**
     * Configuration values whose back-end field is still empty. The AI model and
     * address only travel together with an AI key, the default address and model
     * of a bundle without any AI configured are not worth copying.
     *
     * @return array<string, string>
     */
    private function pending(): array
    {
        $schema = $this->db->createSchemaManager();

        if (!$schema->tablesExist(['tl_neo_websitecheck_settings'])) {
            return [];
        }

        $columns = array_change_key_case($schema->listTableColumns('tl_neo_websitecheck_settings'));

        foreach (array_keys($this->settings->configKeys()) as $column) {
            if (!isset($columns[$column])) {
                return [];
            }
        }

        $row = $this->db->fetchAssociative('SELECT * FROM tl_neo_websitecheck_settings ORDER BY id ASC LIMIT 1') ?: [];
        $config = $this->settings->configKeys();

        if ('' === $config['ai_api_key']) {
            unset($config['ai_model'], $config['ai_base_url']);
        }

        $pending = [];

        foreach ($config as $column => $value) {
            if ('' !== $value && '' === trim((string) ($row[$column] ?? ''))) {
                $pending[$column] = $value;
            }
        }

        return $pending;
    }
}
