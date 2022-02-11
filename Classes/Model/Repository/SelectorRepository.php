<?php

namespace Localizationteam\Localizer\Model\Repository;

use Localizationteam\Localizer\Constants;
use PDO;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for the module 'Selector' for the 'localizer' extension.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class SelectorRepository extends AbstractRepository
{
    public function checkForRecordsOnPage(int $pid, string $table): bool
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $count = $queryBuilder->count('*')->from($table)->where($queryBuilder->expr()->eq(
            'pid',
            $pid
        ))->execute()->fetchOne();

        return $count > 0;
    }

    public function findRecordByPid(int $pid, string $table)
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable($table);

        // TODO: Do we need the restrictions here?
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq('pid', $pid)
            )
            ->execute()
            ->fetchAssociative();
    }

    /**
     * Creates a new cart, when this option is selected in the cart selector
     *
     * @param int $pageId
     * @param int $localizerId
     * @return int
     */
    public function createNewCart(int $pageId, int $localizerId): int
    {
        $localizerLanguages = $this->getLocalizerLanguages($localizerId);

        $databaseConnection = self::getConnectionPool()->getConnectionForTable(Constants::TABLE_LOCALIZER_CART);
        $databaseConnection->insert(
            Constants::TABLE_LOCALIZER_CART,
            [
                'pid' => (int)$pageId,
                'uid_local' => (int)$localizerId,
                'source_locale' => 1,
                'all_locale' => 1,
                'crdate' => time(),
                'cruser_id' => (int)$this->getBackendUser()->user['uid'],
                'status' => Constants::STATUS_CART_ADDED,
                'tstamp' => time(),
            ],
            [
                PDO::PARAM_INT,
                PDO::PARAM_INT,
                PDO::PARAM_INT,
                PDO::PARAM_INT,
                PDO::PARAM_INT,
                PDO::PARAM_INT,
                PDO::PARAM_INT,
                PDO::PARAM_INT,
            ]
        );

        $cartId = $databaseConnection->lastInsertId(Constants::TABLE_LOCALIZER_CART);
        self::getConnectionPool()
            ->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_LANGUAGE_MM)
            ->insert(Constants::TABLE_LOCALIZER_LANGUAGE_MM)
            ->values(
                [
                    'pid' => $pageId,
                    'uid_local' => (int)$cartId,
                    'uid_foreign' => (int)$localizerLanguages['source'],
                    'tablenames' => 'static_languages',
                    'source' => Constants::TABLE_LOCALIZER_CART,
                    'ident' => 'source',
                    'sorting' => 1,
                ]
            )
            ->execute();

        return $cartId;
    }

    /**
     * Stores the configuration of the selected cart
     *
     * @param int $pageId
     * @param int $cartId
     * @param array $configuration
     */
    public function storeConfiguration(int $pageId, int $cartId, array $configuration)
    {
        self::getConnectionPool()
            ->getConnectionForTable(Constants::TABLE_LOCALIZER_CART)
            ->update(
                Constants::TABLE_LOCALIZER_CART,
                [
                    'configuration' => json_encode(
                        [
                            'pid' => (int)$pageId,
                            'tstamp' => time(),
                            'tables' => $configuration['tables'],
                            'languages' => $configuration['languages'],
                            'start' => $configuration['start'],
                            'end' => $configuration['end'],
                            'sortexports' => $configuration['sortexports'],
                            'plainxmlexports' => $configuration['plainxmlexports'],
                        ]
                    ),
                ],
                [
                    'uid' => (int)$cartId,
                ],
                [
                    PDO::PARAM_STR,
                ]
            );
    }

    /**
     * Stores the items of the selected cart
     *
     * @param array $pageIds
     * @param int $cartId
     * @param array $configuration
     * @param array $storedTriples
     */
    public function storeCart(array $pageIds, int $cartId, array $configuration, array $storedTriples)
    {
        if (empty($storedTriples)) {
            $storedTriples = $this->loadStoredTriples($pageIds, $cartId);
        }
        $checkedTriples = GeneralUtility::_GP('localizerSelectorCart');
        $checkedValues = [];
        $pageId = key($pageIds);
        if (!empty($checkedTriples)) {
            foreach ($checkedTriples as $tableName => $records) {
                if (!empty($records) && $configuration['tables'][$tableName]) {
                    foreach ($records as $recordId => $languages) {
                        if (!empty($languages)) {
                            foreach ($languages as $languageId => $checked) {
                                if ($configuration['languages'][$languageId]) {
                                    $identifier = md5($tableName . '.' . $recordId . '.' . $languageId);
                                    $checkedValues[$identifier] = [
                                        'pid' => (int)$pageId,
                                        'identifier' => $identifier,
                                        'cart' => $cartId,
                                        'tablename' => $tableName,
                                        'recordId' => (int)$recordId,
                                        'languageId' => (int)$languageId,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
        $insertValues = array_diff_assoc($checkedValues, $storedTriples);
        $deleteValues = array_diff_assoc($storedTriples, $checkedValues);
        if (!empty($insertValues)) {
            self::getConnectionPool()
                ->getConnectionForTable(Constants::TABLE_CARTDATA_MM)
                ->bulkInsert(
                    Constants::TABLE_CARTDATA_MM,
                    $insertValues,
                    [
                        'pid',
                        'identifier',
                        'cart',
                        'tablename',
                        'recordId',
                        'languageId',
                    ],
                    [
                        PDO::PARAM_INT,
                        PDO::PARAM_STR,
                        PDO::PARAM_INT,
                        PDO::PARAM_STR,
                        PDO::PARAM_INT,
                        PDO::PARAM_INT,
                    ]
                );
        }
        if (!empty($deleteValues)) {
            $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_CARTDATA_MM);
            $queryBuilder
                ->delete(Constants::TABLE_CARTDATA_MM)
                ->where(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq(
                            'pid',
                            (int)$pageId
                        ),
                        $queryBuilder->expr()->in(
                            'identifier',
                            $queryBuilder->createNamedParameter(
                                array_keys($deleteValues),
                                Connection::PARAM_STR_ARRAY
                            )
                        ),
                        $queryBuilder->expr()->eq(
                            'cart',
                            $cartId
                        )
                    )
                )
                ->execute();
        }
    }

    /**
     * Loads all items that might already be in the cart
     *
     * @param array $pageIds
     * @param int $cartId
     * @return array
     */
    public function loadStoredTriples(array $pageIds, int $cartId): array
    {
        $pageIds = implode(',', GeneralUtility::intExplode(',', implode(',', array_keys($pageIds))));
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_CARTDATA_MM);
        $queryBuilder->getRestrictions()->removeAll();
        $triples = $queryBuilder
            ->select('*')
            ->from(Constants::TABLE_CARTDATA_MM)
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->in(
                        'pid',
                        $pageIds
                    ),
                    $queryBuilder->expr()->eq(
                        'cart',
                        (int)$cartId
                    )
                )
            )
            ->execute()
            ->fetchAllAssociative();
        $storedTriples = [];
        if (!empty($triples)) {
            foreach ($triples as $triple) {
                $storedTriples[$triple['identifier']] = $triple;
            }
        }
        return $storedTriples;
    }

    /**
     * Stores the configuration for the L10nmgr export
     *
     * @param int $pageId
     * @param int $localizerId
     * @param int $cartId
     * @param array $configuration
     * @return int
     */
    public function storeL10nmgrConfiguration(int $pageId, int $localizerId, int $cartId, array $configuration): int
    {
        if ($localizerId > 0 && $cartId > 0) {
            $localizerLanguages = $this->getLocalizerLanguages($localizerId);
            if (!empty($localizerLanguages)) {
                $databaseConnection = self::getConnectionPool()->getConnectionForTable(Constants::TABLE_L10NMGR_CONFIGURATION);
                $databaseConnection->insert(
                    Constants::TABLE_L10NMGR_CONFIGURATION,
                    [
                        'pid' => (int)$pageId,
                        'title' => 'Cart Configuration ' . (int)$cartId,
                        'sourceLangStaticId' => (int)$localizerLanguages['source'],
                        'filenameprefix' => 'cart_' . (int)$cartId . '_',
                        'depth' => -2,
                        'tablelist' => implode(',', array_keys($configuration['tables'])),
                        'crdate' => time(),
                        'tstamp' => time(),
                        'cruser_id' => $this->getBackendUser()->user['uid'],
                        'pretranslatecontent' => 0,
                        'overrideexistingtranslations' => 1,
                        'sortexports' => (int)$configuration['sortexports'],
                        'tx_localizer_id' => (int)$localizerId,
                    ],
                    [
                        PDO::PARAM_INT,
                        PDO::PARAM_STR,
                        PDO::PARAM_INT,
                        PDO::PARAM_STR,
                        PDO::PARAM_INT,
                        PDO::PARAM_STR,
                        PDO::PARAM_INT,
                        PDO::PARAM_INT,
                        PDO::PARAM_INT,
                        PDO::PARAM_INT,
                        PDO::PARAM_INT,
                        PDO::PARAM_INT,
                        PDO::PARAM_INT,
                    ]
                );
                return $databaseConnection->lastInsertId(Constants::TABLE_L10NMGR_CONFIGURATION);
            }
        }
        return 0;
    }

    /**
     * Stores the configuration for the L10nmgr export
     *
     * @param int $uid
     * @param int $localizerId
     * @param int $cartId
     * @param array $pageIds
     * @param string $excludeItems
     */
    public function updateL10nmgrConfiguration(
        int $uid,
        int $localizerId,
        int $cartId,
        array $pageIds,
        string $excludeItems
    ) {
        if ($localizerId > 0 && $cartId > 0) {
            $pageIds = implode(',', GeneralUtility::intExplode(',', implode(',', array_keys($pageIds))));
            self::getConnectionPool()
                ->getConnectionForTable(Constants::TABLE_L10NMGR_CONFIGURATION)
                ->update(
                    Constants::TABLE_L10NMGR_CONFIGURATION,
                    [
                        'tstamp' => time(),
                        'exclude' => $excludeItems,
                        'pages' => $pageIds,
                    ],
                    [
                        'uid' => $uid,
                    ],
                    [
                        PDO::PARAM_INT,
                        PDO::PARAM_STR,
                        PDO::PARAM_STR,
                    ]
                );
        }
    }

    /**
     * Finalizes the selected cart and makes it unavailable for the selector
     *
     * @param int $localizerId
     * @param int $cartId
     * @param int $configurationId
     * @param string $deadline
     */
    public function finalizeCart(int $localizerId, int $cartId, int $configurationId, string $deadline = '')
    {
        if ($cartId > 0) {
            self::getConnectionPool()
                ->getConnectionForTable(Constants::TABLE_LOCALIZER_CART)
                ->update(
                    Constants::TABLE_LOCALIZER_CART,
                    [
                        'uid_foreign' => (int)$configurationId,
                        'status' => CONSTANTS::STATUS_CART_FINALIZED,
                        'action' => CONSTANTS::ACTION_EXPORT_FILE,
                        'deadline' => strtotime($deadline, time()),
                        'tstamp' => time(),
                    ],
                    [
                        'uid' => $cartId,
                    ],
                    [
                        PDO::PARAM_INT,
                        PDO::PARAM_INT,
                        PDO::PARAM_INT,
                        PDO::PARAM_INT,
                        PDO::PARAM_INT,
                    ]
                );
            self::getConnectionPool()
                ->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_L10NMGR_MM)
                ->insert(Constants::TABLE_LOCALIZER_L10NMGR_MM)
                ->values(
                    [
                        'uid_local' => $localizerId,
                        'uid_foreign' => $configurationId,
                    ]
                )
                ->execute();
            $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_L10NMGR_MM);
            $countConfigurations = $queryBuilder
                ->count('*')
                ->from(Constants::TABLE_LOCALIZER_L10NMGR_MM)
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        $localizerId
                    )
                )
                ->execute()
                ->fetchColumn(0);
            self::getConnectionPool()
                ->getConnectionForTable(Constants::TABLE_LOCALIZER_SETTINGS)
                ->update(
                    Constants::TABLE_LOCALIZER_SETTINGS,
                    [
                        'l10n_cfg' => $countConfigurations,
                    ],
                    [
                        'uid' => $localizerId,
                    ],
                    [
                        PDO::PARAM_INT,
                    ]
                );
        }
    }

    /**
     * Gets all records of any selected translatable table
     * together with information about the carts the record might have been put into
     * and additional information about translations and changes
     * that might have been made after sending the record to translation
     *
     * For performance reasons it is essential to collect as much of that information
     * within just one query or while generating the array of result rows
     *
     * @param int $id
     * @param array $pageIds
     * @param array $translatableTables
     * @param array $configuration
     * @return array
     */
    public function getRecordsOnPages(
        int $id,
        array $pageIds,
        array $translatableTables,
        array $configuration = []
    ): array {
        $records = [];
        $referencedRecords = [];
        $identifiedStatus = [];
        $start = 0;
        $end = 0;
        $pageIds = GeneralUtility::intExplode(',', implode(',', array_keys($pageIds)));
        if (!empty($configuration['start'])) {
            $start = strtotime($configuration['start']);
        }
        if (!empty($configuration['end'])) {
            $end = strtotime($configuration['end']);
        }
        foreach (array_keys($translatableTables) as $table) {
            if ($table === 'sys_file_metadata') {
                continue;
            }
            $tstampField = $GLOBALS['TCA'][$table]['ctrl']['tstamp'];
            $deleteField = $GLOBALS['TCA'][$table]['ctrl']['delete'];
            $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
            $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
            $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder
                ->selectLiteral(
                    $table . '.*,
                    triples.languageId localizer_language,
                    MAX(carts.status) localizer_status,
                    MAX(carts.tstamp) last_action,
                    GROUP_CONCAT(DISTINCT translations.' . $transOrigPointerField . ') translated,
                    GROUP_CONCAT(DISTINCT outdated.' . $transOrigPointerField . ') changed,
                    MAX(outdated.tstamp) outdated'
                )
                ->from($table);
            if ($table === 'pages') {
                $queryBuilder->leftJoin(
                    $table,
                    $table,
                    'translations',
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq(
                            $table . '.uid',
                            $queryBuilder->quoteIdentifier('translations.pid')
                        ),
                        $queryBuilder->expr()->gt(
                            'translations.' . $languageField,
                            0
                        ),
                        $queryBuilder->expr()->gte(
                            'translations.' . $tstampField,
                            $queryBuilder->quoteIdentifier($table . '.' . $tstampField)
                        ),
                        $queryBuilder->expr()->gte(
                            'translations.deleted',
                            0
                        )
                    )
                )->leftJoin(
                    $table,
                    Constants::TABLE_CARTDATA_MM,
                    'triples',
                    $queryBuilder->expr()->eq(
                        'triples.tablename',
                        $queryBuilder->createNamedParameter($table, PDO::PARAM_STR)
                    )
                )->leftJoin(
                    'triples',
                    Constants::TABLE_LOCALIZER_CART,
                    'carts',
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->gt(
                            'carts.status',
                            10
                        ),
                        $queryBuilder->expr()->eq(
                            'triples.cart',
                            $queryBuilder->quoteIdentifier('carts.uid')
                        ),
                        $queryBuilder->expr()->gte(
                            'carts.deleted',
                            0
                        )
                    )
                )->leftJoin(
                    $table,
                    $table,
                    'outdated',
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq(
                            'outdated.pid',
                            $queryBuilder->quoteIdentifier($table . '.uid')
                        ),
                        $queryBuilder->expr()->gt(
                            'outdated.' . $languageField,
                            0
                        ),
                        $queryBuilder->expr()->lt(
                            'outdated.' . $tstampField,
                            $queryBuilder->quoteIdentifier($table . '.tstamp')
                        ),
                        $queryBuilder->expr()->gte(
                            'outdated.deleted',
                            0
                        )
                    )
                )->where(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->in(
                            $table . '.pid',
                            $pageIds
                        ),
                        $queryBuilder->expr()->eq(
                            $table . '.' . $transOrigPointerField,
                            0
                        )
                    )
                );
            } else {
                $queryBuilder->leftJoin(
                    $table,
                    $table,
                    'translations',
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->in(
                            'translations.pid',
                            $pageIds
                        ),
                        $queryBuilder->expr()->gt(
                            'translations.' . $transOrigPointerField,
                            0
                        ),
                        $queryBuilder->expr()->gte(
                            'translations.' . $tstampField,
                            $queryBuilder->quoteIdentifier($table . '.' . $tstampField)
                        ),
                        $deleteField ? $queryBuilder->expr()->gte(
                            'translations.deleted',
                            0
                        ) : null
                    )
                )->leftJoin(
                    $table,
                    Constants::TABLE_CARTDATA_MM,
                    'triples',
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq(
                            'triples.tablename',
                            $queryBuilder->createNamedParameter($table, PDO::PARAM_STR)
                        ),
                        $queryBuilder->expr()->eq(
                            'triples.recordid',
                            $queryBuilder->quoteIdentifier($table . '.uid')
                        )
                    )
                )->leftJoin(
                    'triples',
                    Constants::TABLE_LOCALIZER_CART,
                    'carts',
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->gt(
                            'carts.status',
                            10
                        ),
                        $queryBuilder->expr()->eq(
                            'triples.cart',
                            $queryBuilder->quoteIdentifier('carts.uid')
                        ),
                        $queryBuilder->expr()->gte(
                            'carts.deleted',
                            0
                        )
                    )
                )->leftJoin(
                    $table,
                    $table,
                    'outdated',
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->in(
                            'outdated.pid',
                            $pageIds
                        ),
                        $queryBuilder->expr()->gt(
                            'outdated.' . $transOrigPointerField,
                            0
                        ),
                        $queryBuilder->expr()->lt(
                            'outdated.' . $tstampField,
                            $queryBuilder->quoteIdentifier($table . '.' . $tstampField)
                        ),
                        $deleteField ? $queryBuilder->expr()->gte(
                            'outdated.deleted',
                            0
                        ) : null
                    )
                )->where(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->in(
                            $table . '.pid',
                            $pageIds
                        ),
                        $queryBuilder->expr()->eq(
                            $table . '.' . $languageField,
                            0
                        )
                    )
                );
            }
            if (BackendUtility::isTableWorkspaceEnabled($table) && ExtensionManagementUtility::isLoaded('workspaces')) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq(
                        $table . '.t3ver_wsid',
                        0
                    )
                );
            }
            if ($start) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->gte(
                        $table . '.' . $tstampField,
                        (int)$start
                    )
                );
            }
            if ($end) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->lte(
                        $table . '.' . $tstampField,
                        (int)$end
                    )
                );
            }
            $queryBuilder->groupBy(
                'localizer_language',
                $table . '.uid'
            );
            if ($configuration['sortexports']) {
                $sortBy = '';
                if (isset($GLOBALS['TCA'][$table]['ctrl']['sortby'])) {
                    $sortBy = $GLOBALS['TCA'][$table]['ctrl']['sortby'];
                } else {
                    if (isset($GLOBALS['TCA'][$table]['ctrl']['default_sortby'])) {
                        $sortBy = $GLOBALS['TCA'][$table]['ctrl']['default_sortby'];
                    }
                }
                $TSconfig = BackendUtility::getPagesTSconfig($id);
                if (isset($TSconfig['tx_l10nmgr']) && isset($TSconfig['tx_l10nmgr']['sortexports']) && isset($TSconfig['tx_l10nmgr']['sortexports'][$table])) {
                    $sortBy = $TSconfig['tx_l10nmgr']['sortexports'][$table];
                }
                if ($sortBy) {
                    foreach (QueryHelper::parseOrderBy((string)$sortBy) as $orderPair) {
                        [$fieldName, $order] = $orderPair;
                        $queryBuilder->addOrderBy($table . '.' . $fieldName, $order);
                    }
                }
            }

            $statement = $queryBuilder->execute();

            $records[$table] = [];
            $checkedRecords = [];
            while ($record = $statement->fetchAssociative()) {
                if ($record['localizer_status'] && $record['outdated'] > $record['last_action'] && GeneralUtility::inList(
                    $record['changed'],
                    0
                )) {
                    $record['localizer_status'] = 71;
                }
                $identifier = md5($table . '.' . $record['uid'] . '.' . $record['localizer_language']);
                $identifiedStatus[$identifier]['status'] = $record['localizer_status'] ? $record['localizer_status'] : 10;
                if (!empty($record['translated'])) {
                    $translatedLaguages = GeneralUtility::intExplode(',', $record['translated']);
                    foreach ($translatedLaguages as $languageId) {
                        $identifier = md5($table . '.' . $record['uid'] . '.' . $languageId);
                        $identifiedStatus[$identifier]['status'] = 70;
                    }
                }
                if (!empty($record['changed'])) {
                    $changedLanguages = GeneralUtility::intExplode(',', $record['changed']);
                    foreach ($changedLanguages as $languageId) {
                        $identifier = md5($table . '.' . $record['uid'] . '.' . $languageId);
                        $identifiedStatus[$identifier]['status'] = 71;
                    }
                }
                if (!isset($checkedRecords[$table][$record['uid']])) {
                    $checkedRecords[$table][$record['uid']] = true;
                    $relations = $this->checkRelations(
                        $record,
                        $table,
                        $translatableTables
                    );
                    if (!empty($relations)) {
                        foreach ($relations as $referenceTable => $referenceInfo) {
                            if (isset($configuration['tables'][$referenceTable])) {
                                foreach ($referenceInfo as $referenceUid => $referencedRecord) {
                                    if ((int)$referencedRecord['pid'] === $id) {
                                        $referencedRecords[$table][$record['uid']][$referenceTable][$referenceUid] = $referencedRecord;
                                    }
                                }
                            }
                        }
                    }
                    $records[$table][$record['uid']] = $record;
                }
            }
            foreach ($referencedRecords as $referencingRecord) {
                foreach ($referencingRecord as $referencedTables) {
                    foreach ($referencedTables as $table => $sortedRecords) {
                        foreach ($sortedRecords as $record) {
                            unset($records[$table][$record['uid']]);
                        }
                    }
                }
            }
            unset($checkedRecords);
        }
        return [
            'records' => $records,
            'referencedRecords' => $referencedRecords,
            'identifiedStatus' => $identifiedStatus,
        ];
    }
}
