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
}