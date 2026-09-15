<?php

declare(strict_types=1);

namespace Netwerk\AiImageMetadata\Domain\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class CopyrightRepository
{
    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    /**
     * All copyright notices of images that are actually referenced by a
     * visible record, deduplicated and sorted.
     *
     * @return list<string>
     */
    public function findUsedCopyrights(): array
    {
        $references = $this->findReferencesWithCopyright();

        // Group by parent table so visibility can be checked per table.
        $copyrightsByTable = [];
        foreach ($references as $reference) {
            $table = (string)$reference['tablenames'];
            $uid = (int)$reference['uid_foreign'];

            if ($table === '' || !isset($GLOBALS['TCA'][$table])) {
                continue;
            }

            $copyrightsByTable[$table][$uid][] = (string)$reference['copyright'];
        }

        $copyrights = [];
        foreach ($copyrightsByTable as $table => $copyrightsByUid) {
            $visibleUids = $this->filterVisibleUids($table, array_keys($copyrightsByUid));

            foreach ($visibleUids as $uid) {
                foreach ($copyrightsByUid[$uid] as $copyright) {
                    $copyrights[] = $copyright;
                }
            }
        }

        return $this->deduplicate($copyrights);
    }

    private function findReferencesWithCopyright(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->setRestrictions(
            GeneralUtility::makeInstance(FrontendRestrictionContainer::class)
        );

        return $queryBuilder
            ->select('metadata.copyright', 'reference.tablenames', 'reference.uid_foreign')
            ->from('sys_file_reference', 'reference')
            ->innerJoin(
                'reference',
                'sys_file_metadata',
                'metadata',
                $queryBuilder->expr()->eq(
                    'metadata.file',
                    $queryBuilder->quoteIdentifier('reference.uid_local')
                )
            )
            ->where(
                $queryBuilder->expr()->neq(
                    'metadata.copyright',
                    $queryBuilder->createNamedParameter('')
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @param list<int> $uids
     * @return list<int>
     */
    private function filterVisibleUids(string $table, array $uids): array
    {
        if ($uids === []) {
            return [];
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->setRestrictions(
            GeneralUtility::makeInstance(FrontendRestrictionContainer::class)
        );

        $result = $queryBuilder
            ->select('uid')
            ->from($table)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($uids, Connection::PARAM_INT_ARRAY)
                )
            )
            ->executeQuery()
            ->fetchFirstColumn();

        return array_map(intval(...), $result);
    }

    /**
     * @param list<string> $copyrights
     * @return list<string>
     */
    private function deduplicate(array $copyrights): array
    {
        $unique = [];

        foreach ($copyrights as $copyright) {
            $copyright = trim($copyright);

            if ($copyright === '') {
                continue;
            }

            // Case-insensitive deduplication, but keep the first spelling.
            $unique[mb_strtolower($copyright)] ??= $copyright;
        }

        $result = array_values($unique);
        usort($result, strnatcasecmp(...));

        return $result;
    }
}