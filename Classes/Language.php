<?php

declare(strict_types=1);

namespace Localizationteam\Localizer;

use Exception;
use Localizationteam\Localizer\Model\Repository\LanguageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Language
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
trait Language
{
    /**
     * @param array $row
     * @return string
     * @throws Exception
     */
    protected function getIso2ForLocale(array $row): string
    {
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
        if ($iso2 === '') {
            throw new Exception('ID ' . $row['target_locale'] . ' can not be found in static languages or TYPO3 SiteConfiguration or is missing the hreflang configuration. Please inform your admin!');
        }

        return $iso2;
    }

    /**
     * @param array $row
     * @return bool
     */
    protected function translateAll(array $row): bool
    {
        $translateAll = false;
        if (isset($row['all_locale'])) {
            $translateAll = (bool)$row['all_locale'];
        }
        return $translateAll;
    }
}
