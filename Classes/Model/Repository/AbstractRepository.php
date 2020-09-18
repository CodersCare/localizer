<?php

namespace Localizationteam\Localizer\Model\Repository;

use Localizationteam\Localizer\BackendUser;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\DatabaseConnection;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for the module 'Selector' for the 'localizer' extension.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 */
class AbstractRepository
{
    use BackendUser, DatabaseConnection;

    /**
     * @param int $localizerId
     * @return array|FALSE|NULL
     */
    public function getLocalizerLanguages($localizerId)
    {
        return $this->getDatabaseConnection()
            ->exec_SELECTgetSingleRow(
                'MAX(sourceLanguage.uid) source, GROUP_CONCAT(targetLanguage.uid) target',
                Constants::TABLE_LOCALIZER_SETTINGS . ' settings' .
                ' LEFT OUTER JOIN ' . Constants::TABLE_LOCALIZER_LANGUAGE_MM . ' sourceMM' .
                ' ON settings.uid = sourceMM.uid_local 
                            AND sourceMM.tablenames = "' . Constants::TABLE_STATIC_LANGUAGES . '" 
                            AND sourceMM.ident = "source"
                            AND sourceMM.source = "' . Constants::TABLE_LOCALIZER_SETTINGS . '"' .
                ' LEFT OUTER JOIN ' . Constants::TABLE_STATIC_LANGUAGES . ' sourceLanguage ON sourceLanguage.uid = sourceMM.uid_foreign' .
                ' LEFT OUTER JOIN ' . Constants::TABLE_LOCALIZER_LANGUAGE_MM . ' targetMM' .
                ' ON settings.uid = targetMM.uid_local 
                            AND targetMM.tablenames = "' . Constants::TABLE_STATIC_LANGUAGES . '" 
                            AND targetMM.ident = "target"
                            AND targetMM.source = "' . Constants::TABLE_LOCALIZER_SETTINGS . '"' .
                ' LEFT OUTER JOIN ' . Constants::TABLE_STATIC_LANGUAGES . ' targetLanguage ON targetLanguage.uid = targetMM.uid_foreign',
                'settings.uid = ' . (int)$localizerId,
                'settings.uid'
            );
    }

    /**
     * @param array $systemLanguages
     * @return array|FALSE|NULL
     */
    public function getStaticLanguages($systemLanguages)
    {
        $systemLanguageUids = '0';
        foreach ($systemLanguages as $language) {
            $systemLanguageUids .= ',' . (int)$language['uid'];
        }
        $languages = $this->getDatabaseConnection()
            ->exec_SELECTgetRows(
                '*',
                Constants::TABLE_SYS_LANGUAGE,
                'uid IN (' . $systemLanguageUids . ') ' . BackendUtility::BEenableFields(
                    Constants::TABLE_SYS_LANGUAGE
                ),
                '',
                '',
                '',
                'uid'
            );

        if (!empty($languages)) {
            foreach ($systemLanguages as $language) {
                if (isset($languages[$language['uid']])) {
                    $languages[$language['uid']]['flagIcon'] = $language['flagIcon'];
                }
            }
        }

        return $languages;
    }

    /**
     * Loads the configuration of the selected cart
     *
     * @param int $cartId
     * @return array
     */
    public function loadConfiguration($cartId)
    {
        $selectedCart = $this->getDatabaseConnection()
            ->exec_SELECTgetSingleRow(
                '*',
                Constants::TABLE_LOCALIZER_CART,
                'uid = ' . (int)$cartId
            );
        if (!empty($selectedCart['configuration'])) {
            $configuration = json_decode($selectedCart['configuration'], true);
            if (!empty($configuration)) {
                return [
                    'tables' => $configuration['tables'],
                    'languages' => $configuration['languages'],
                    'start' => $configuration['start'],
                    'end' => $configuration['end'],
                ];
            }
        }
        return [];
    }

    /**
     * Loads available localizer settings
     *
     * @return array|NULL
     */
    public function loadAvailableLocalizers()
    {
        $availableLocalizeres = $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            Constants::TABLE_LOCALIZER_SETTINGS,
            'uid > 0 ' . BackendUtility::BEenableFields(
                Constants::TABLE_LOCALIZER_SETTINGS
            ) . ' AND ' . Constants::TABLE_LOCALIZER_SETTINGS . '.deleted=0',
            '',
            '',
            '',
            'uid'
        );
        return $availableLocalizeres;
    }

    /**
     * Loads available carts, which have not been finalized yet
     *
     * @param int $localizerId
     * @return array|NULL
     */
    public function loadAvailableCarts($localizerId)
    {
        $availableCarts = $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            Constants::TABLE_LOCALIZER_CART,
            'cruser_id = ' . $this->getBackendUser()->user['uid'] .
            ' AND uid_local = ' . (int)$localizerId .
            ' AND status = ' . Constants::STATUS_CART_ADDED . BackendUtility::BEenableFields(
                Constants::TABLE_LOCALIZER_CART
            ) . ' AND ' . Constants::TABLE_LOCALIZER_CART . '.deleted=0'
        );
        return $availableCarts;
    }

    /**
     * Loads available pages for carts
     *
     * @param int $pageId
     * @param int $cartId
     * @return array|NULL
     */
    public function loadAvailablePages($pageId, $cartId)
    {
        $pageId = (int)$pageId;
        $cartId = (int)$cartId;
        $availablePages = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'DISTINCT pid',
            Constants::TABLE_CARTDATA_MM,
            'pid > 0 AND cart = ' . $cartId,
            '',
            '',
            '',
            'pid'
        );
        if ($pageId > 0) {
            $availablePages[$pageId] = [
                'pid' => $pageId,
            ];
        }
        if (!empty($availablePages)) {
            $pageTitles = $this->getDatabaseConnection()->exec_SELECTgetRows(
                'uid,title',
                'pages',
                'uid IN (' . implode(',', array_keys($availablePages)) . ')',
                '',
                '',
                '',
                'uid'
            );
            foreach ($availablePages as $pageId => &$pageData) {
                $pageData['cart'] = $cartId;
                $pageData['title'] = $pageTitles[$pageId]['title'];
            }
        }
        return $availablePages;
    }

    /**
     * Loads available pages for carts
     *
     * @param int $cartId
     * @return array|NULL
     */
    public function loadAvailableLanguages($cartId)
    {
        $availableLanguages = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'DISTINCT languageId',
            Constants::TABLE_CARTDATA_MM,
            'languageId > 0 AND cart = ' . (int)$cartId,
            '',
            '',
            '',
            'languageId'
        );
        return $availableLanguages;
    }

    /**
     * Loads available pages for carts
     *
     * @param int $cartId
     * @return array|NULL
     */
    public function loadAvailableTables($cartId)
    {
        $availableTables = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'DISTINCT tablename',
            Constants::TABLE_CARTDATA_MM,
            'cart = ' . (int)$cartId,
            '',
            '',
            '',
            'tablename'
        );
        return $availableTables;
    }

    /**
     * Gets all related child records of a parent record based on the reference index
     *
     * @param array $record
     * @param int $table
     * @param array $translatableTables
     * @return array $relations
     */
    protected function checkRelations($record, $table, $translatableTables)
    {
        $relations = [];
        foreach ($GLOBALS['TCA'][$table]['columns'] as $fieldName => $column) {
            $configuration = $column['config'];
            if (
                (
                    $configuration['type'] === 'inline'
                    || $configuration['type'] === 'group'
                    || $configuration['type'] === 'select'
                )
                && (
                    !empty($configuration['foreign_table'])
                    || !empty($configuration['MM'])
                )
                && isset($translatableTables[$configuration['foreign_table']])
            ) {
                /**@var $relationHandler RelationHandler * */
                $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
                $relationHandler->start(
                    $fieldName,
                    $configuration['foreign_table'],
                    $configuration['MM'] ?? '',
                    $record['uid'],
                    $table,
                    $configuration
                );
                if (!empty($relationHandler->tableArray[$configuration['foreign_table']])) {
                    $relationHandler->getFromDB();
                    $relations = array_merge_recursive($relations, $relationHandler->results);
                }
            }
        }

        return $relations;
    }

    /**
     * Returns the WHERE clause " AND NOT [tablename].[deleted-field]" if a deleted-field
     * is configured in $GLOBALS['TCA'] for the tablename, $table
     * This function should ALWAYS be called in the backend for selection on tables which
     * are configured in $GLOBALS['TCA'] since it will ensure consistent selection of records,
     * even if they are marked deleted (in which case the system must always treat them as non-existent!)
     * In the frontend a function, ->enableFields(), is known to filter hidden-field, start- and endtime
     * and fe_groups as well. But that is a job of the frontend, not the backend. If you need filtering
     * on those fields as well in the backend you can use ->BEenableFields() though.
     *
     * @param string $table Table name present in $GLOBALS['TCA']
     * @param string $tableAlias Table alias if any
     * @return string WHERE clause for filtering out deleted records, eg " AND tablename.deleted=0
     */
    public static function deleteClause($table, $tableAlias = '')
    {
        if (empty($GLOBALS['TCA'][$table]['ctrl']['delete'])) {
            return '';
        }
        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table)
            ->expr();
        return ' AND ' . $expressionBuilder->eq(
                ($tableAlias ?: $table) . '.' . $GLOBALS['TCA'][$table]['ctrl']['delete'],
                0
            );
    }

}
