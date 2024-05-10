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
