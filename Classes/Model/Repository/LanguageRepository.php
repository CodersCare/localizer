<?php

namespace Localizationteam\Localizer\Model\Repository;

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LanguageRepository extends AbstractRepository
{
    public function getIsoTwoCodeByLocale(string $locale, int $pid = 1): string
    {
        $locale = str_replace('-', '_', $locale);
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pid);
        $languages = $site->getAllLanguages();

        DebugUtility::debug($languages);
        $filteredLanguages = array_filter($languages, function (SiteLanguage $language) use ($locale) {
            return strpos($language->getLocale(), $locale) !== false;
        });

        /** @var SiteLanguage $language */
        $language = array_shift($filteredLanguages);

        if ($language instanceof SiteLanguage) {
            return mb_strtoupper($language->getTypo3Language());
        }

        return '';
    }
}
