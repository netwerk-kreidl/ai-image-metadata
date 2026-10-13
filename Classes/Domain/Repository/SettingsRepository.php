<?php

declare(strict_types=1);

namespace Netwerk\AiImageMetadata\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;

final readonly class SettingsRepository
{
    private const TABLE = 'tx_aiimagemetadata_settings';

    private const DEFAULTS = [
        'business_name' => '',
        'business_type' => '',
        'location' => '',
        'target_group' => '',
        'tone' => 'neutral',
        'output_language' => 'de',
        'alt_max' => 125,
        'title_max' => 60,
        'keywords' => '',
        'forbidden_words' => '',
        'examples' => '',
        'extra_instructions' => '',
    ];

    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    /**
     * @return array<string, mixed> Einstellungen, fehlende Werte mit Defaults aufgefüllt
     */
    public function get(): array
    {
        $row = $this->connectionPool->getConnectionForTable(self::TABLE)
            ->select(['*'], self::TABLE, [], [], ['uid' => 'ASC'], 1)
            ->fetchAssociative();

        return array_merge(self::DEFAULTS, is_array($row) ? $row : []);
    }

    /**
     * Es gibt genau einen Datensatz auf Root-Ebene. Beim ersten Aufruf wird er angelegt.
     */
    public function ensureRecordUid(): int
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE);
        $uid = $connection->select(['uid'], self::TABLE, [], [], ['uid' => 'ASC'], 1)->fetchOne();

        if ($uid !== false) {
            return (int)$uid;
        }

        $connection->insert(self::TABLE, [
            'pid' => 0,
            'crdate' => time(),
            'tstamp' => time(),
        ]);

        return (int)$connection->lastInsertId();
    }
}
