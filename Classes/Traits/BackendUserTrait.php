<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Traits;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait BackendUserTrait
{
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? GeneralUtility::makeInstance(BackendUserAuthentication::class);
    }
}
