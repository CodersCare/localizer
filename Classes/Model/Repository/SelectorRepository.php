<?php

namespace Localizationteam\Localizer\Model\Repository;

use Localizationteam\Localizer\Constants;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for the module 'Selector' for the 'localizer' extension.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 */
class SelectorRepository extends AbstractRepository
{
    /**
     * Creates a new cart, when this option is selected in the cart selector
     *
     * @param int $pageId
     * @param int $localizerId
     * @return int
     */
    public function createNewCart($pageId, $localizerId)
    {
        $localizerLanguages = $this->getLocalizerLanguages($localizerId);

        $fields = [
            'pid'           => (int)$pageId,
            'uid_local'     => (int)$localizerId,
            'source_locale' => 1,
            'all_locale'    => 1,
            'crdate'        => time(),
            'cruser_id'     => (int)$this->getBackendUser()->user['uid'],
            'status'        => (int)Constants::STATUS_CART_ADDED,
            'tstamp'        => time(),
        ];
        $this->getDatabaseConnection()
            ->exec_INSERTquery(
                Constants::TABLE_LOCALIZER_CART,
                $fields,
                ['pid', 'uid_local', 'source_locale', 'all_locale', 'crdate', 'cruser_id', 'status', 'tstamp']
            );
        $cartId = $this->getDatabaseConnection()->sql_insert_id();
        $fields = [
            'pid'         => (int)$pageId,
            'uid_local'   => (int)$cartId,
            'uid_foreign' => (int)$localizerLanguages['source'],
            'tablenames'  => 'static_languages',
            'source'      => Constants::TABLE_LOCALIZER_CART,
            'ident'       => 'source',
            'sorting'     => 1,
        ];
        $this->getDatabaseConnection()
            ->exec_INSERTquery(
                Constants::TABLE_LOCALIZER_LANGUAGE_MM,
                $fields,
                ['pid', 'uid_local', 'uid_foreign', 'sorting']
            );
        return $cartId;
    }

    /**
     * Stores the configuration of the selected cart
     *
     * @param int $pageId
     * @param int $cartId
     * @param $configuration
     */
    public function storeConfiguration($pageId, $cartId, $configuration)
    {
        $fieldArray = [
            'configuration' => json_encode(
                [
                    'pid'       => (int)$pageId,
                    'tstamp'    => time(),
                    'tables'    => $configuration['tables'],
                    'languages' => $configuration['languages'],
                    'start'     => $configuration['start'],
                    'end'       => $configuration['end'],
                ]
            ),
        ];
        $this->getDatabaseConnection()
            ->exec_UPDATEquery(
                Constants::TABLE_LOCALIZER_CART,
                'uid = ' . (int)$cartId,
                $fieldArray
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
    public function storeCart($pageIds, $cartId, $configuration, $storedTriples)
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
                                        'pid'        => (int)$pageId,
                                        'identifier' => $identifier,
                                        'cart'       => (int)$cartId,
                                        'tablename'  => $tableName,
                                        'recordId'   => (int)$recordId,
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
            $this->getDatabaseConnection()
                ->exec_INSERTmultipleRows(
                    Constants::TABLE_CARTDATA_MM,
                    ['pid', 'identifier', 'cart', 'tablename', 'recordId', 'languageId'],
                    $insertValues,
                    'cart,recordId,languageId'
                );
        }
        if (!empty($deleteValues)) {
            $this->getDatabaseConnection()
                ->exec_DELETEquery(
                    Constants::TABLE_CARTDATA_MM,
                    "pid = " . $pageId . " AND identifier IN ('" . implode("','",
                        array_keys($deleteValues)) . "') AND cart = " . (int)$cartId
                );
        }
    }

    /**
     * Loads all items that might already be in the cart
     *
     * @param $pageIds
     * @param $cartId
     * @return array|NULL
     */
    public function loadStoredTriples($pageIds, $cartId)
    {
        $pageIds = implode(',', GeneralUtility::intExplode(',', implode(',', array_keys($pageIds))));
        $storedTriples = $this->getDatabaseConnection()
            ->exec_SELECTgetRows(
                '*',
                Constants::TABLE_CARTDATA_MM,
                'pid IN (' . $pageIds . ') AND cart = ' . (int)$cartId,
                '',
                '',
                '',
                'identifier'
            );
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
    public function storeL10nmgrConfiguration($pageId, $localizerId, $cartId, $configuration)
    {
        if ($localizerId > 0 && $cartId > 0) {
            $localizerLanguages = $this->getLocalizerLanguages($localizerId);
            if (!empty($localizerLanguages)) {
                $this->getDatabaseConnection()
                    ->exec_INSERTquery(
                        Constants::TABLE_L10NMGR_CONFIGURATION,
                        [
                            'pid'                          => (int)$pageId,
                            'title'                        => 'Cart Configuration ' . (int)$cartId,
                            'sourceLangStaticId'           => (int)$localizerLanguages['source'],
                            'filenameprefix'               => 'cart_' . (int)$cartId . '_',
                            'depth'                        => -2,
                            'tablelist'                    => implode(',', array_keys($configuration['tables'])),
                            'crdate'                       => time(),
                            'tstamp'                       => time(),
                            'cruser_id'                    => $this->getBackendUser()->user['uid'],
                            'pretranslatecontent'          => 0,
                            'overrideexistingtranslations' => 1,
                        ]
                    );
            }

            return $this->getDatabaseConnection()->sql_insert_id();
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
    public function updateL10nmgrConfiguration($uid, $localizerId, $cartId, $pageIds, $excludeItems)
    {
        if ($localizerId > 0 && $cartId > 0) {
            $pageIds = implode(',', GeneralUtility::intExplode(',', implode(',', array_keys($pageIds))));
            $this->getDatabaseConnection()
                ->exec_UPDATEquery(
                    Constants::TABLE_L10NMGR_CONFIGURATION,
                    'uid = ' . (int)$uid,
                    [
                        'tstamp'  => time(),
                        'exclude' => $excludeItems,
                        'pages'   => $pageIds,
                    ],
                    ['tstamp']
                );
        }
    }

    /**
     * Finalizes the selected cart and makes it unavailable for the selector
     *
     * @param int $localizerId
     * @param int $cartId
     * @param int $configurationId
     */
    public function finalizeCart($localizerId, $cartId, $configurationId)
    {
        if ($cartId > 0) {
            $this->getDatabaseConnection()
                ->exec_UPDATEquery(
                    Constants::TABLE_LOCALIZER_CART,
                    'uid = ' . (int)$cartId,
                    [
                        'uid_foreign' => (int)$configurationId,
                        'status'      => CONSTANTS::STATUS_CART_FINALIZED,
                        'action'      => CONSTANTS::ACTION_EXPORT_FILE,
                        'tstamp'      => time(),
                    ],
                    ['uid_foreign', 'status', 'time']
                );
            $this->getDatabaseConnection()
                ->exec_INSERTquery(
                    Constants::TABLE_LOCALIZER_L10NMGR_MM,
                    [
                        'uid_local'   => (int)$localizerId,
                        'uid_foreign' => (int)$configurationId,
                    ],
                    ['uid_local', 'uid_foreign']
                );
            $countConfigurations = $this->getDatabaseConnection()
                ->exec_SELECTcountRows(
                    '*',
                    Constants::TABLE_LOCALIZER_L10NMGR_MM,
                    'uid_local = ' . (int)$localizerId
                );
            $this->getDatabaseConnection()
                ->exec_UPDATEquery(
                    Constants::TABLE_LOCALIZER_SETTINGS,
                    'uid = ' . (int)$localizerId,
                    [
                        'l10n_cfg' => $countConfigurations,
                    ],
                    ['l10n_cfg']
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
     * @param $id
     * @param $pageIds
     * @param $translatableTables
     * @param array $configuration
     * @return array
     */
    public function getRecordsOnPages($id, $pageIds, $translatableTables, $configuration = [])
    {
        $records = [];
        $referencedRecords = [];
        $identifiedStatus = [];
        $start = 0;
        $end = 0;
        $pageIds = implode(',', GeneralUtility::intExplode(',', implode(',', array_keys($pageIds))));
        if (!empty($configuration['start'])) {
            $start = strtotime($configuration['start']);
        }
        if (!empty($configuration['end'])) {
            $end = strtotime($configuration['end']);
        }
        foreach (array_keys($translatableTables) as $table) {
            $additionalWhere = '';
            if (BackendUtility::isTableWorkspaceEnabled($table)) {
                $additionalWhere .= ' AND ' . $table . '.t3ver_id = 0';
            }
            if ($start) {
                $additionalWhere .= ' AND ' . $table . '.tstamp >= ' . $start;
            }
            if ($end) {
                $additionalWhere .= ' AND ' . $table . '.tstamp <= ' . $end;
            }
            if ($table === 'pages') {
                $res = $this->getDatabaseConnection()->exec_SELECTquery(
                    'pages.*, 
                    triples.languageId localizer_language, 
                    MAX(carts.status) localizer_status, 
                    MAX(carts.tstamp) last_action, 
                    GROUP_CONCAT(DISTINCT translations.sys_language_uid) translated,
                    GROUP_CONCAT(DISTINCT outdated.sys_language_uid) changed,
                    MAX(outdated.tstamp) outdated',
                    'pages ' .
                    ' LEFT OUTER JOIN pages_language_overlay translations 
                        ON translations.pid = pages.uid 
                           AND translations.tstamp >= pages.tstamp' .
                    ' LEFT OUTER JOIN ' . Constants::TABLE_CARTDATA_MM . ' triples 
                        ON triples.tablename = "pages_language_overlay"' .
                    ' LEFT OUTER JOIN ' . Constants::TABLE_LOCALIZER_CART . ' carts 
                        ON carts.status > 10 
                           AND triples.cart = carts.uid' .
                    ' LEFT OUTER JOIN pages_language_overlay outdated 
                        ON outdated.pid = pages.uid 
                           AND outdated.tstamp < pages.tstamp',
                    'triples.recordid IN (translations.uid,outdated.uid) 
                        AND pages.uid IN (' . $pageIds . ') ' . BackendUtility::deleteClause($table) . $additionalWhere,
                    'triples.languageId, pages.uid',
                    'pages.sorting'
                );
            } else {
                $res = $this->getDatabaseConnection()->exec_SELECTquery(
                    $table . '.*, 
                    triples.languageId localizer_language, 
                    MAX(carts.status) localizer_status, 
                    MAX(carts.tstamp) last_action, 
                    GROUP_CONCAT(DISTINCT translations.sys_language_uid) translated,
                    GROUP_CONCAT(DISTINCT outdated.sys_language_uid) changed,
                    MAX(outdated.tstamp) outdated',
                    $table .
                    ' LEFT OUTER JOIN ' . $table . ' translations 
                        ON translations.pid IN (' . $pageIds . ' ) 
                           AND translations . ' . $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] . ' = ' . $table . '.uid 
                           AND translations.tstamp >= ' . $table . '.tstamp' .
                    ' LEFT OUTER JOIN ' . Constants::TABLE_CARTDATA_MM . ' triples 
                        ON triples.tablename = "' . $table . '" 
                           AND triples.recordid = ' . $table . '.uid' .
                    ' LEFT OUTER JOIN ' . Constants::TABLE_LOCALIZER_CART . ' carts 
                        ON carts.status > 10 
                           AND triples.cart = carts.uid' .
                    ' LEFT OUTER JOIN ' . $table . ' outdated 
                        ON outdated.pid IN ( ' . $pageIds . ' )
                           AND outdated.' . $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] . ' = ' . $table . '.uid 
                           AND outdated.tstamp < ' . $table . '.tstamp
                           ',
                    $table . '.pid IN ( ' . $pageIds . ' ) 
                        AND ' . $table . '.sys_language_uid = 0 ' . BackendUtility::deleteClause($table) . $additionalWhere,
                    'localizer_language, ' . $table . '.uid',
                    $GLOBALS['TCA'][$table]['ctrl']['sortby'] ? $table . '.' . $GLOBALS['TCA'][$table]['ctrl']['sortby'] : ''
                );
            }
            if ($this->getDatabaseConnection()->sql_error()) {
                $this->getDatabaseConnection()->sql_free_result($res);
                return null;
            }
            $records[$table] = [];
            $checkedRecords = [];
            while ($record = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                if ($record['localizer_status'] && $record['outdated'] > $record['last_action'] && GeneralUtility::inList($record['changed'],
                        0)
                ) {
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
                                foreach($referenceInfo as $referenceUid  => $referencedRecord) {
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
            $this->getDatabaseConnection()->sql_free_result($res);
        }
        return [
            'records'           => $records,
            'referencedRecords' => $referencedRecords,
            'identifiedStatus'  => $identifiedStatus,
        ];
    }

}