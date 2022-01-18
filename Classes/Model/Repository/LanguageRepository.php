<?php

namespace Localizationteam\Localizer\Model\Repository;

use Localizationteam\Localizer\Constants;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LanguageRepository extends AbstractRepository
{
    public function getIsoTwoCodeBySystemLanguageId(int $localeId, int $pid = 1): string
    {
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pid);
        $languages = $site->getAllLanguages();

        if ($languages[$localeId] instanceof SiteLanguage) {
            return $languages[$localeId]->getHreflang();
        }

        return '';
    }

    /**
     * @param int $uidLocal
     * @param string $table
     * @return array
     */
    protected function getAllTargetLanguageUids(int $uidLocal, string $table): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_LOCALIZER_LANGUAGE_MM
        );
        $queryBuilder->getRestrictions()
            ->removeAll();
        $rows = $queryBuilder
            ->select('uid_foreign')
            ->from(Constants::TABLE_LOCALIZER_LANGUAGE_MM)
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        $uidLocal
                    ),
                    $queryBuilder->expr()->eq(
                        'tablenames',
                        $queryBuilder->createNamedParameter('static_languages', Connection::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'source',
                        $queryBuilder->createNamedParameter($table, Connection::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'ident',
                        $queryBuilder->createNamedParameter('target', Connection::PARAM_STR)
                    )
                )
            )
            ->execute()
            ->fetchAllAssociative();
        $languageUids = [];
        if (!empty($rows)) {
            $languageUids = array_column($rows, 'uid_foreign');
        }
        return $languageUids;
    }

    /**
     * @param array $uidList
     * @param bool $fixUnderLine
     * @return array
     */
    protected function getStaticLanguagesCollateLocale(array $uidList, bool $fixUnderLine = false): array
    {
        $collateLocale = [];
        if (count($uidList) > 0) {
            $field = 'lg_collate_locale';
            $orgField = $field;
            $uidList = GeneralUtility::intExplode(',', implode(',', $uidList), true);
            if ($fixUnderLine === true) {
                $field = 'REPLACE(' . $field . ', "_", "-") as ' . $field;
            }
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
                Constants::TABLE_STATIC_LANGUAGES
            );
            $queryBuilder->getRestrictions()
                ->removeAll();
            $rows = $queryBuilder
                ->selectLiteral($field)
                ->from(Constants::TABLE_STATIC_LANGUAGES)
                ->where(
                    $queryBuilder->expr()->in(
                        'uid',
                        $queryBuilder->createNamedParameter($uidList, Connection::PARAM_INT_ARRAY)
                    )
                )
                ->execute()
                ->fetchAllAssociative();
            if (!empty($rows)) {
                $locale = [];
                foreach ($rows as $row) {
                    if (isset($row[$orgField])) {
                        $locale[$row[$orgField]] = $row;
                    }
                }
                $collateLocale = array_keys($locale);
            }
        }
        return $collateLocale;
    }

    /**
     * @param int $uid
     * @return int
     */
    protected function getSystemLanguageIdByTargetLanguage(int $uid): int
    {
        $systemLanguageId = 0;
        if ($uid > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
                Constants::TABLE_SYS_LANGUAGE
            );
            $queryBuilder->getRestrictions()
                ->removeAll();
            $systemLanguageId = $queryBuilder
                ->select('uid')
                ->from(Constants::TABLE_SYS_LANGUAGE)
                ->where(
                    $queryBuilder->expr()->eq(
                        'static_lang_isocode',
                        $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                    )
                )
                ->execute()
                ->fetchColumn();
        }
        return $systemLanguageId;
    }
}
