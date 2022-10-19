<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Model\Repository;

use Doctrine\DBAL\Connection as ConnectionAlias;
use Localizationteam\Localizer\Constants;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LanguageRepository extends AbstractRepository
{
    /**
     * @param int $localeId
     * @param int $pid
     * @return string
     * @throws SiteNotFoundException
     */
    public function getIsoTwoCodeBySystemLanguageId(int $localeId, int $pid = 1): string
    {
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pid);
        $languages = $site->getAllLanguages();

        if (($languages[$localeId] ?? null) instanceof SiteLanguage) {
            return $languages[$localeId]->getHreflang();
        }

        return '';
    }

    /**
     * @param int $uidLocal
     * @param string $table
     * @return array
     */
    public function getAllTargetLanguageUids(int $uidLocal, string $table): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_LANGUAGE_MM);
        $queryBuilder->getRestrictions()
            ->removeAll();
        $result = $queryBuilder
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
            ->execute();
        $rows = $this->fetchAllAssociative($result);
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
    public function getStaticLanguagesCollateLocale(array $uidList, bool $fixUnderLine = false): array
    {
        $collateLocale = [];
        if (count($uidList) > 0) {
            $field = 'lg_collate_locale';
            $orgField = $field;
            $uidList = GeneralUtility::intExplode(',', implode(',', $uidList), true);
            if ($fixUnderLine === true) {
                $field = 'REPLACE(' . $field . ', "_", "-") as ' . $field;
            }
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(Constants::TABLE_STATIC_LANGUAGES);
            $queryBuilder->getRestrictions()
                ->removeAll();
            $result = $queryBuilder
                ->selectLiteral($field)
                ->from(Constants::TABLE_STATIC_LANGUAGES)
                ->where(
                    $queryBuilder->expr()->in(
                        'uid',
                        $queryBuilder->createNamedParameter($uidList, ConnectionAlias::PARAM_INT_ARRAY)
                    )
                )
                ->execute();
            $rows = $this->fetchAllAssociative($result);
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

    public function getSystemLanguageIdByTargetLanguage(int $uid): int
    {
        if ($uid <= 0) {
            return 0;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Constants::TABLE_SYS_LANGUAGE);
        $queryBuilder->getRestrictions()
            ->removeAll();
        $result = $queryBuilder
            ->select('uid')
            ->from(Constants::TABLE_SYS_LANGUAGE)
            ->where(
                $queryBuilder->expr()->eq(
                    'static_lang_isocode',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                )
            )
            ->execute();

        return (int)$this->fetchOne($result);
    }
}
