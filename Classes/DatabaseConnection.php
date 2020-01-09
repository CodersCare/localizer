<?php

namespace Localizationteam\Localizer;

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
    /**
     * @return \TYPO3\CMS\Typo3DbLegacy\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}