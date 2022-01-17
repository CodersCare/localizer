<?php

namespace Localizationteam\Localizer\Model\Repository;

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LanguageRepository extends AbstractRepository
{
    public function getIsoTwoCodeByTargetLocaleId(int $localeId, int $pid = 1): string
    {
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pid);
        $languages = $site->getAllLanguages();

        if ($languages[$localeId] instanceof SiteLanguage) {
            return $languages[$localeId]->getHreflang();
        }

        return '';
    }
}
