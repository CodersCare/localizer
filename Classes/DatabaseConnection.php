<?php

namespace Localizationteam\Localizer;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * DatabaseConnection
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 *
 */
trait DatabaseConnection
{
    /*******************************************
     *
     * SQL-related, selecting records, searching
     *
     *******************************************/

    /**
     * @return \TYPO3\CMS\Typo3DbLegacy\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /*******************************************
     *
     * SQL-related, selecting records, searching
     *
     *******************************************/
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
        trigger_error('BackendUtility::deleteClause() will be removed in TYPO3 v10.0. Add the delete statement directly in your SQL statement via the DeletedRestriction.', E_USER_DEPRECATED);
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