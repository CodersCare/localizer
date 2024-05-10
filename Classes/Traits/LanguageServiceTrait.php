<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Traits;

use TYPO3\CMS\Core\Localization\LanguageService;

trait LanguageServiceTrait
{
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
