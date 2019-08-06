<?php

namespace Localizationteam\Localizer;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * BackendUser
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
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