<?php

declare(strict_types=1);

namespace Localizationteam\Localizer;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * BackendUser
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
trait BackendUser
{
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
