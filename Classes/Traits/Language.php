<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Traits;

use Exception;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Model\Repository\LanguageRepository;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Language
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
trait Language
{
    /**
     * @throws Exception
     */
    protected function getIso2ForLocale(array $row): string
    {
        /** @var Typo3Version $typo3Version */
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);

        if ($typo3Version->getMajorVersion() < 12) {
            /** @var LanguageRepository $languageRepository */
            $languageRepository = GeneralUtility::makeInstance(LanguageRepository::class);
            $targetLanguages = $languageRepository->getAllTargetLanguageUids($row['uid'], Constants::TABLE_EXPORTDATA_MM);
            $iso2 = '';
            if (count($targetLanguages) > 0) {
                $collateLocale = $languageRepository->getStaticLanguagesCollateLocale($targetLanguages, true);
                if (count($collateLocale) > 0) {
                    $iso2 = $collateLocale[0];
                } else {
                    $systemLanguageId = $languageRepository->getSystemLanguageIdByTargetLanguage($targetLanguages[0]);
                    if ($systemLanguageId > 0) {
                        $iso2 = $languageRepository->getIsoTwoCodeBySystemLanguageId($systemLanguageId, $row['pid']);
                    }
                }
            }
        } else {
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($row['pid']);
            return $site->getLanguageById($row['target_language'])->getLocale()->__toString();
        }

        return $iso2;
    }

    protected function translateAll(array $row): bool
    {
        $translateAll = false;
        if (isset($row['all_locale'])) {
            $translateAll = (bool)$row['all_locale'];
        }
        return $translateAll;
    }
}
