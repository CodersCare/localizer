<?php

namespace Localizationteam\Localizer;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * BackendUser $COMMENT$
 *
 * @author      Peter Russ<peter.russ@4many.net>
 * @package     TYPO3
 * @date        20160628-0107
 * @subpackage  localizer
 *
 */
trait BackendUser
{
    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}