<?php

namespace Localizationteam\Localizer;

/**
 * DatabaseConnection $COMMENT$
 *
 * @author      Peter Russ<peter.russ@4many.net>
 * @package     TYPO3
 * @date        20160628-0103
 * @subpackage  localizer
 *
 */
trait DatabaseConnection
{
    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}